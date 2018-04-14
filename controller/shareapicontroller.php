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


class ShareApiController extends ApiController {

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getShares($noteid, $shared_with_me, $reshares) {

	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function share($noteid, $shareType, $shareWith, $publicUpload, $password, $permissions) {

	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function unshare($itemSource, $shareType, $shareWith) {

	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function setpermissions($itemSource, $shareType, $shareWith, $permissions) {

	}
}