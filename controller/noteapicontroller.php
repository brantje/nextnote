<?php
/**
 * Nextcloud - NextNote
 *
 *
 * @copyright Copyright (c) 2017, Sander Brand (brantje@gmail.com)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\NextNote\Controller;

use OCA\NextNote\Db\Notebook;
use OCA\NextNote\Db\Note;
use OCA\NextNote\Fixtures\ShareFix;
use OCA\NextNote\Service\NotebookService;
use OCA\NextNote\Service\NoteService;
use OCA\NextNote\ShareBackend\NextNoteShareBackend;
use OCA\NextNote\Utility\NotFoundJSONResponse;
use OCA\NextNote\Utility\UnauthorizedJSONResponse;
use OCA\NextNote\Utility\Utils;
use \OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use OCP\IUserManager;
use OCP\Share;


class NoteApiController extends ApiController {

	private $config;
	private $noteService;
	private $shareBackend;
	private $userManager;
	private $shareManager;
	private $notebookService;

	public function __construct($appName, IRequest $request,
								ILogger $logger, IConfig $config, NoteService $noteService, NotebookService $groupService,
								NextNoteShareBackend $shareBackend, IUserManager $userManager, Share\IManager $shareManager) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->noteService = $noteService;
		$this->notebookService = $groupService;
		$this->shareBackend = $shareBackend;
		$this->userManager = $userManager;
		$this->shareManager = $shareManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 * @param int|bool $deleted
	 * @param string|bool $notebook_id
	 * @return JSONResponse
	 */
	public function index($deleted = false, $notebook_id = false) {
		$uid = \OC::$server->getUserSession()->getUser()->getUID();

		if(!empty($notebook_id)){
			$notebook_id = $this->notebookService->find($notebook_id)->getId();
		}
		$results = $this->noteService->findNotesFromUser($uid, $deleted, $notebook_id);
		foreach ($results as &$note) {
			if (is_array($note)) {
				$note = $this->noteService->find($note['id']);
			}
			$note = $note->jsonSerialize();
			$note = $this->formatApiResponse($note);

		}
		return new JSONResponse($results);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 */
	public function get($id) {
		$result = $this->noteService->find($id);
		if (!$result) {
			return new NotFoundJSONResponse();
		}
		//@todo Check access
		$result = $result->jsonSerialize();
		return new JSONResponse($this->formatApiResponse($result));
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function create($title, $notebook_id, $content) {
		if ($title == "" || !$title) {
			return new JSONResponse(['error' => 'title is missing']);
		}

		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$note = new Note();
		$note->setName($title);
		$note->setUid($uid);
		$note->setGuid(Utils::GUID());
		$note->setNote($content);
		$note->setMtime(time());
		$note->setDeleted(0);

		if(!empty($notebook_id)){
			$notebook = $this->notebookService->find($notebook_id);
			if($notebook instanceof Notebook) {
				$note->setNotebook($notebook->getId());
			} else {
				return new JSONResponse(['error' => 'Notebook not found']);
			}
		}

		$result = $this->noteService->create($note)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_create_note', ['note' => $note]);
		return new JSONResponse($this->formatApiResponse($result));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update($id, $title, $content, $deleted, $notebook_id) {
		if ($title == "" || !$title) {
			return new JSONResponse(['error' => 'title is missing']);
		}

		$note = $this->noteService->find($id);
		if (!$note) {
			return new NotFoundJSONResponse();
		}

		if(!$note->getGuid()){
			$note->setGuid(Utils::GUID());
		}

		if (!$this->shareBackend->checkPermissions(Constants::PERMISSION_UPDATE, $note)) {
			return new UnauthorizedJSONResponse();
		}
		if(!empty($notebook_id)){
			$notebook = $this->notebookService->find($notebook_id);
			if($notebook instanceof Notebook) {
				$note->setNotebook($notebook->getId());
			} else {
				return new JSONResponse(['error' => 'Notebook not found']);
			}
		}
		$note->setName($title);
		$note->setNote($content);
		$note->setDeleted($deleted);

		$results = $this->noteService->update($note)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_update_note', ['note' => $note]);
		return new JSONResponse($this->formatApiResponse($results));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function delete($id) {
		$entity = $this->noteService->find($id);
		if (!$entity) {
			return new NotFoundJSONResponse();
		}

		if (!$this->shareBackend->checkPermissions(Constants::PERMISSION_DELETE, $entity)) {
			return new UnauthorizedJSONResponse();
		}

		$this->noteService->delete($id);
		$result = (object)['success' => true];
		\OC_Hook::emit('OCA\NextNote', 'post_delete_note', ['note_id' => $id]);
		return new JSONResponse($result);
	}

	/**
	 * @param $note array
	 * @return array
	 */
	private function formatApiResponse($note) {
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$acl = [
			'permissions' => Constants::PERMISSION_ALL
		];
		if ($uid !== $note['uid']) {
			$aclRoles = ShareFix::getItemSharedWith('nextnote', $note['id'], 'populated_shares');
			$acl['permissions'] = $aclRoles['permissions'];
		}
		$note['owner'] = Utils::getUserInfo($note['uid']);
		$note['permissions'] = $acl['permissions'];

		$shared_with = ShareFix::getUsersItemShared('nextnote', $note['id'], $note['uid']);
		foreach ($shared_with as &$u) {
			$info = Utils::getUserInfo($u);
			if($info) {
				$u = $info;
			}
		}

		$note['shared_with'] = ($note['uid'] == $uid) ? $shared_with : [$uid];
		unset($note['uid']);
		return $note;
	}
}
