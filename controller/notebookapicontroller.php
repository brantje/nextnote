<?php
/**
 * Nextcloud - NextNote
 *
 * @copyright Copyright (c) 2015, Ben Curtis <ownclouddev@nosolutions.com>
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
use OCA\NextNote\Fixtures\ShareFix;
use OCA\NextNote\Service\GroupService;
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


class NotebookApiController extends ApiController {

	private $config;
	private $groupService;
	private $shareBackend;
	private $userManager;

	public function __construct($appName, IRequest $request,
								ILogger $logger, IConfig $config, GroupService $noteService, NextNoteShareBackend $shareBackend, IUserManager $userManager) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->groupService = $noteService;
		$this->shareBackend = $shareBackend;
		$this->userManager = $userManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 * @param int|bool $deleted
	 * @param string|bool $group
	 * @return JSONResponse
	 */
	public function index($deleted = false, $group = false) {
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$results = $this->groupService->find(null, $uid);
		foreach ($results as &$group) {
			$group = $group->jsonSerialize();
			$this->formatApiResponse($group);

		}
		return new JSONResponse($results);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 */
	public function get($id) {
		$result = $this->groupService->find($id);
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
	public function create($name, $color, $parent_id) {
		if ($name == "" || !$name) {
			return new JSONResponse(['error' => 'name is missing']);
		}
		$group = [
			'parent_id' => $parent_id,
			'name' => $name,
			'color' => $color,
			'guid' => Utils::GUID()
		];

		if($this->groupService->findByName($name)){
			return new JSONResponse(['error' => 'Group already exists']);
		}

		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$result = $this->groupService->create($group, $uid)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_create_group', ['group' => $group]);
		return new JSONResponse($this->formatApiResponse($result));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update($id, $name, $color, $parent_id) {
		if ($name == "" || !$name) {
			return new JSONResponse(['error' => 'title is missing']);
		}


		$group = [
			'parent_id' => $parent_id,
			'name' => $name,
			'color' => $color,
		];
		//@TODO for sharing add access check
		$entity = $this->groupService->find($id);
		if (!$entity) {
			return new NotFoundJSONResponse();
		}


		if (!$this->shareBackend->checkPermissions(Constants::PERMISSION_UPDATE, $entity)) {
			return new UnauthorizedJSONResponse();
		}

		$results = $this->groupService->update($group)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_update_group', ['group' => $group]);
		return new JSONResponse($this->formatApiResponse($results));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function delete($id) {
		$entity = $this->groupService->find($id);
		if (!$entity) {
			return new NotFoundJSONResponse();
		}

		if (!$this->shareBackend->checkPermissions(Constants::PERMISSION_DELETE, $entity)) {
			return new UnauthorizedJSONResponse();
		}

		$this->groupService->delete($id);
		$result = (object)['success' => true];
		\OC_Hook::emit('OCA\NextNote', 'post_delete_group', ['group_id' => $id]);
		return new JSONResponse($result);
	}

	/**
	 * @param $group array
	 * @return array
	 */
	private function formatApiResponse($group) {
		return $group;
	}
}
