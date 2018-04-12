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


use OCA\NextNote\Service\NoteService;
use OCA\NextNote\Service\SettingsService;
use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;


class PageController extends Controller {

	private $userId;
	private $config;
	private $noteService;

	public function __construct($appName, IRequest $request, $userId,
								SettingsService $settings,
								NoteService $noteService) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->config = $settings;
		$this->noteService = $noteService;
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
		$shareMode = $this->config->getAppSetting('sharemode', 'merge'); // merge or standalone
		$params = array('user' => $this->userId, 'shareMode' => $shareMode, 'config'=> $this->config->getSettings());

		if($this->config->getUserSetting('first_user', '1') === '1'){
			$this->noteService->createExampleNote($this->userId);
			$this->config->setUserSetting('first_user', '0');
		}

		$response = new TemplateResponse('nextnote', 'main', $params);
		$ocVersion = \OCP\Util::getVersion();
		if ($ocVersion[0] > 8 || ($ocVersion[0] == 8 && $ocVersion[1] >= 1)) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$csp->addAllowedImageDomain('data:');
			$csp->addAllowedImageDomain('blob:');
			$csp->addAllowedFrameDomain('data:');

			$allowedFrameDomains = array(
				'https://www.youtube.com'
			);
			foreach ($allowedFrameDomains as $domain) {
				$csp->addAllowedFrameDomain($domain);
			}

			$csp->addAllowedScriptDomain("'nonce-test'");
			$csp->addAllowedScriptDomain("*");
			$response->setContentSecurityPolicy($csp);
		}
		return $response;
	}
}
