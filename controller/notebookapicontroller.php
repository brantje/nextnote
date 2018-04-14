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
use OCA\NextNote\Service\NotebookService;
use OCA\NextNote\Utility\NotFoundJSONResponse;
use OCA\NextNote\Utility\UnauthorizedJSONResponse;
use OCA\NextNote\Utility\Utils;
use \OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use OCP\IUserManager;


class NotebookApiController extends ApiController {

	private $config;
	private $notebookService;
	private $userManager;

	public function __construct($appName, IRequest $request,
								ILogger $logger, IConfig $config, NotebookService $notebookService, IUserManager $userManager) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->notebookService = $notebookService;
		$this->userManager = $userManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 * @param int|bool $deleted
	 * @return JSONResponse
	 */
	public function index($deleted = false) {
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$result = $this->notebookService->findNotebooksFromUser($uid, $deleted);
		$results = $result;
		if($result instanceof Notebook){
			$results = [];
			/**
			 * @var $result Notebook
			 */
			$results[$result->getId()] = $result;
		}

		return new JSONResponse($results);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 * @param $id
	 * @return NotFoundJSONResponse|JSONResponse
	 */
	public function get($id) {
		$result = $this->notebookService->find($id);
		if (!$result) {
			return new NotFoundJSONResponse();
		}
		//@todo Check access
		$result = $result->jsonSerialize();
		return new JSONResponse($result);
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $name
	 * @param $color
	 * @param $parent_id
	 * @return JSONResponse
	 */
	public function create($name, $color, $parent_id) {
		if ($name == "" || !$name) {
			return new JSONResponse(['error' => 'name is missing']);
		}


		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$notebook = new Notebook();
		$notebook->setName($name);
		$notebook->setParentId($parent_id);
		$notebook->setUid($uid);
		$notebook->setColor($color);
		$notebook->setGuid(Utils::GUID());

		/*
		if($this->notebookService->findByName($name)){
			return new JSONResponse(['error' => 'Group already exists']);
		}*/

		$result = $this->notebookService->create($notebook, $uid)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_create_notebook', ['notebook' => $notebook]);
		return new JSONResponse($result);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $id
	 * @param $name
	 * @param $color
	 * @param $parent_id
	 * @return NotFoundJSONResponse|UnauthorizedJSONResponse|JSONResponse
	 */
	public function update($id, $name, $color, $parent_id) {
		if ($name == "" || !$name) {
			return new JSONResponse(['error' => 'title is missing']);
		}

		//@TODO for sharing add access check
		$notebook = $this->notebookService->find($id);
		if (!$notebook) {
			return new NotFoundJSONResponse();
		}

		$notebook->setName($name);
		$notebook->setParentId($parent_id);
		$notebook->setColor($color);

		$results = $this->notebookService->update($notebook)->jsonSerialize();
		\OC_Hook::emit('OCA\NextNote', 'post_update_notebook', ['notebook' => $notebook]);
		return new JSONResponse($results);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $id
	 * @return NotFoundJSONResponse|UnauthorizedJSONResponse|JSONResponse
	 */
	public function delete($id) {
		$entity = $this->notebookService->find($id);
		if (!$entity) {
			return new NotFoundJSONResponse();
		}

		$this->notebookService->delete($id);
		$result = (object)['success' => true];
		\OC_Hook::emit('OCA\NextNote', 'post_delete_notebook', ['notebook_id' => $id]);
		return new JSONResponse($result);
	}
}
