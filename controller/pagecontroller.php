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


use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\ContentSecurityPolicy;
use \OCP\Util;

class PageController extends Controller {

    private $userId;

    public function __construct($appName, IRequest $request, $userId){
        parent::__construct($appName, $request);
        $this->userId = $userId;
    }


    /**
     * CAUTION: the @Stuff turn off security checks, for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
		$params = array('user' => $this->userId);
		$response = new TemplateResponse('ownnote', 'list', $params);
		$ocVersion = \OCP\Util::getVersion();
		if ($ocVersion[0] > 8 || ($ocVersion[0] == 8 && $ocVersion[1] >= 1)) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$csp->addAllowedImageDomain('data:');
			$response->setContentSecurityPolicy($csp);
		}
		return $response;
    }
}
