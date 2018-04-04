<?php
/**
 * Nextcloud - namespace OCA\Nextnote
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 * @copyright Copyright (c) 2016, Marcos Zuriaga Miguel (wolfi@wolfi.es)
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

use OCP\IL10N;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\ApiController;
use OCP\IRequest;
use OCA\NextNote\Service\SettingsService;

class SettingsController extends ApiController {
	private $userId;
	private $settings;

	public function __construct(
		$AppName,
		IRequest $request,
		$userId,
		SettingsService $settings,
		IL10N $l) {
		parent::__construct(
			$AppName,
			$request,
			'GET, POST, DELETE, PUT, PATCH, OPTIONS',
			'Authorization, Content-Type, Accept',
			86400);
		$this->settings = $settings;
		$this->l = $l;
		$this->userId = $userId;
	}


	/**
	 * Get all settings
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSettings() {
		$settings = $this->settings->getAppSettings();
		return new JSONResponse($settings);
	}

	/**
	 * Save a user setting
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function saveUserSetting($key, $value) {
		$this->settings->setUserSetting($key, $value);
		return new JSONResponse('OK');
	}


	/**
	 * Save a app setting
	 *
	 * @NoCSRFRequired
	 */
	public function saveAdminSetting($field, $value) {
		$this->settings->setAppSetting($field, $value);
		return new JSONResponse('OK');
	}

}