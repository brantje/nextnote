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
use \OCP\IRequest;

\OCP\App::checkAppEnabled('ownnote');



class OwnnoteSharesController extends ApiController {

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function getShares($noteid, $shared_with_me, $reshares) {
		if ($shared_with_me) {
			return \OCP\Share::getItemSharedWith('ownnote', $noteid, 'shares');
		} else if ($reshares) {
			return array_values(\OCP\Share::getItemShared('ownnote', $noteid, 'shares'));
		}
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function share($noteid, $shareType, $shareWith, $publicUpload, $password, $permissions) {
		return \OCP\Share::shareItem('ownnote', intval($noteid), intval($shareType), $shareWith, intval($permissions));
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function unshare($itemSource, $shareType, $shareWith) {
		return \OCP\Share::unshare('ownnote', intval($itemSource), intval($shareType), $shareWith);
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */		
	public function setpermissions($itemSource, $shareType, $shareWith, $permissions) {
		return \OCP\Share::setPermissions('ownnote', intval($itemSource), intval($shareType), $shareWith, intval($permissions));
	}
}
