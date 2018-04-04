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
use OCA\NextNote\Fixtures\ShareFix;


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
		$shareType = intval($shareType);
		//Todo check if resharing is allowed
		if($shareType === 1){
			$result = ShareFix::shareItem('nextnote', intval($noteid), intval($shareType), $shareWith, intval($permissions));
		} else {
			$result = Share::shareItem('nextnote', intval($noteid), intval($shareType), $shareWith, intval($permissions));
		}
		\OC_Hook::emit('OCA\NextNote', 'post_share_note', ['note_id' => $noteid]);
		return $result;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function unshare($itemSource, $shareType, $shareWith) {
		$result = Share::unshare('nextnote', intval($itemSource), intval($shareType), $shareWith);
		\OC_Hook::emit('OCA\NextNote', 'post_unshare_note', ['note_id' => $itemSource]);
		return $result;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setpermissions($itemSource, $shareType, $shareWith, $permissions) {
		$result = ShareFix::setPermissions('nextnote', intval($itemSource), intval($shareType), $shareWith, intval($permissions));
		\OC_Hook::emit('OCA\NextNote', 'post_update_note_share_permissions', ['note_id' => $itemSource]);
		return $result;
	}
}