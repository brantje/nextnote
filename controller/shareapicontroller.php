<?php
/**
 * ownCloud - nextnote
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Ben Curtis <ownclouddev@nosolutions.com>
 * @copyright Ben Curtis 2015
 */

namespace OCA\NextNote\Controller;

use \OCP\AppFramework\ApiController;
use \OCP\IRequest;
use OC\Share\Share;



class ShareApiController extends ApiController {

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getShares($noteid, $shared_with_me, $reshares) {
		if ($shared_with_me) {
			//@FIXME return \OCP\Share::getItemSharedWith('nextnote', $noteid, 'shares');
		} else if ($reshares) {
			return array_values(Share::getItemShared('nextnote', $noteid, 'shares'));
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function share($noteid, $shareType, $shareWith, $publicUpload, $password, $permissions) {
		//Todo check if resharing is allowed
		return Share::shareItem('nextnote', intval($noteid), intval($shareType), $shareWith, intval($permissions));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function unshare($itemSource, $shareType, $shareWith) {
		return Share::unshare('nextnote', intval($itemSource), intval($shareType), $shareWith);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setpermissions($itemSource, $shareType, $shareWith, $permissions) {
		return;
		//@FIXME return \OCP\Share::setPermissions('nextnote', intval($itemSource), intval($shareType), $shareWith, intval($permissions));
	}
}