<?php
/**
 * Nextcloud - ownnote
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

namespace OCA\OwnNote\Controller;

use OCA\OwnNote\Service\OwnNoteService;
use OCA\OwnNote\Utility\NotFoundJSONResponse;
use \OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;


class Ownnotev2ApiController extends ApiController {

	private $backend;
	private $config;
	private $noteService;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, OwnNoteService $noteService) {
		parent::__construct($appName, $request);
		$this->backend = new Backend($config);
		$this->config = $config;
		$this->noteService = $noteService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 */
	public function index() {
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$results = $this->noteService->findNotesFromUser($uid, false);
		return new JSONResponse($results);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @TODO Add etag / lastmodified
	 */
	public function get($id) {
		$results = $this->noteService->find($id);
		if (!$results) {
			return new NotFoundJSONResponse();
		}
		return new JSONResponse($results);
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function create($title, $group, $note) {
		$note = [
			'title' => $title,
			'group' => $group,
			'note' => $note
		];
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$result = $this->noteService->create($note, $uid);
		return new JSONResponse($result);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function update($id, $title, $group, $content) {

		$note = [
			'id' => $id,
			'title' => $title,
			'group' => $group,
			'note' => $content
		];

		$entity = $this->noteService->find($id);
		if (!$entity) {
			return new NotFoundJSONResponse();
		}

		$results = $this->noteService->update($note);
		return new JSONResponse($results);
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

		$results = $this->noteService->delete($id);
		return new JSONResponse($results);
	}

}
