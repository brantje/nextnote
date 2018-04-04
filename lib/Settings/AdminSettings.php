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


namespace OCA\NextNote\Settings;



use OCA\NextNote\Service\SettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
class AdminSettings implements ISettings {


	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;
	private $settingsService;


	/**
	 * Admin constructor.
	 *
	 * @param IL10N $l
	 * @param IURLGenerator $urlGenerator
	 * @param SettingsService $settingsService
	 */
	public function __construct(IL10N $l,
								IURLGenerator $urlGenerator,
								SettingsService $settingsService
	) {
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->settingsService = $settingsService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {

		$params = $this->settingsService->getAppSettings();

		return new TemplateResponse('nextnote', 'admin', $params);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'nextnote';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority() {
		return 0;
	}

}
