<?php
/**
 * Nextcloud - nextnote
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 *
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

namespace OCA\NextNote\Service;

use OCP\IConfig;


class SettingsService {

	private $userId;
	private $config;
	private $appName;
	public $settings;
	public $userSettings;


	private $numeric_settings = array(
		'link_sharing_enabled',

	);

	public function __construct($UserId, IConfig $config, $AppName) {
		$this->userId = $UserId;
		$this->config = $config;
		$this->appName = $AppName;
		$this->settings = array(
			'folder' => $this->config->getAppValue('nextnote', 'folder', ''),
			'sharemode' => $this->config->getAppValue('nextnote', 'sharemode', 'merge'),

		);
		$this->userSettings = array(
			'view_mode' => $this->config->getUserValue($this->userId, 'nextnote', 'view_mode', 'col') // single|col
		);
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function getSettings() {
		$settings = [
			'app' => $this->settings,
			'user' => $this->userSettings
		];
		return $settings;
	}


	/**
	 * Get all app settings
	 *
	 * @return array
	 */
	public function getAppSettings() {
		return $this->settings;
	}

	/**
	 * Get all user settings
	 *
	 * @return array
	 */
	public function gettUserSettings() {
		return $this->userSettings;
	}

	/**
	 * Get a app setting
	 *
	 * @param $key string
	 * @param null $default_value The default value if key does not exist
	 * @return mixed
	 */
	public function getAppSetting($key, $default_value = null) {
		$value = ($this->settings[$key]) ? $this->settings[$key] : $this->config->getAppValue('nextnote', $key, $default_value);
		if (in_array($key, $this->numeric_settings)) {
			$value = intval($value);
		}

		return $value;
	}

	/**
	 * Set a app setting
	 *
	 * @param $key string Setting name
	 * @param $value mixed Value of the setting
	 */
	public function setAppSetting($key, $value) {
		$this->settings[$key] = $value;
		$this->config->setAppValue('nextnote', $key, $value);
	}

	/**
	 * Set a user setting
	 *
	 * @param $key string Setting name
	 * @param $value mixed Value of the setting
	 */

	public function setUserSetting($key, $value) {
		return $this->config->setUserValue($this->userId, 'nextnote', $key, $value);
	}

	/**
	 * Check if an setting is enabled (value of 1)
	 *
	 * @param $setting
	 * @return bool
	 */
	public function isEnabled($setting) {
		$value = intval($this->getAppSetting($setting, false));
		return ($value === 1);
	}
}