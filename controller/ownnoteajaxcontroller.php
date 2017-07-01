<?php
/**
 * ownCloud - ownnote
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Ben Curtis <ownclouddev@nosolutions.com>
 * @copyright Ben Curtis 2015
 */

namespace OCA\OwnNote\Controller;

use \OCP\AppFramework\ApiController;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\Response;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\UserManager;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;

\OCP\App::checkAppEnabled('ownnote');



class OwnnoteAjaxController extends ApiController {

	private $backend;


	public function __construct($appName, IRequest $request, $userManager){
		parent::__construct($appName, $request);
		$this->backend = new Backend($userManager);
	}

	/**
	* AJAX FUNCTIONS
	*/

	/**
	* @NoAdminRequired
	*/
	public function ajaxindex() {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		return $this->backend->getListing($FOLDER, false);
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxannouncement() {
		return $this->backend->getAnnouncement();
	}

	/**
	 * @NoAdminRequired
	 */
	public function ajaxcreate($name, $group) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($name) && isset($group))
			return $this->backend->createNote($FOLDER, $name, $group);
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxdel($nid) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($nid)) {
			return $this->backend->deleteNote($FOLDER, $nid);
		}
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxedit($nid) {
		if (isset($nid)) {
			return $this->backend->editNote($nid);
		}
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxsave($id, $content) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($content)) {
			return $this->backend->saveNote($FOLDER, $id, $content, 0);
		}
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxren($id, $newname, $newgroup) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($id) && isset($newname) && isset($newgroup))
			return $this->backend->renameNote($FOLDER, $id, $newname, $newgroup);
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxdelgroup($group) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($group))
			return $this->backend->deleteGroup($FOLDER, $group);
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxrengroup($group, $newgroup) {
		$FOLDER = \OCP\Config::getAppValue('ownnote', 'folder', '');
		if (isset($group) && isset($newgroup))
			return $this->backend->renameGroup($FOLDER, $group, $newgroup);
	}

	/**
	* @NoAdminRequired
	*/
	public function ajaxversion() {
		return $this->backend->getVersion();
	}

	/**
	*/
	public function ajaxsetval($field, $value) {
		return $this->backend->setAdminVal($field, $value);
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ajaxgetsharemode() {
		return \OCP\Config::getAppValue('ownnote', 'sharemode', '');
	}
}
