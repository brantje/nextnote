<?php
/**
 * Nextcloud - ownnote
 *
 * @copyright Copyright (c) 2015, Ben Curtis <ownclouddev@nosolutions.com>
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
namespace OCA\OwnNote\AppInfo;

use OC\Files\View;


use OCA\OwnNote\Controller\OwnnoteAjaxController;
use OCA\OwnNote\Controller\OwnnoteApiController;
use OCA\OwnNote\Controller\OwnnoteSharesController;
use OCA\OwnNote\Controller\PageController;
use OCP\IConfig;
use OCP\IDBConnection;

use OCP\AppFramework\App;
use OCP\IL10N;
use OCP\Util;

class Application extends App {
	public function __construct() {
		parent::__construct('ownnote');
		$container = $this->getContainer();
		// Allow automatic DI for the View, until we migrated to Nodes API
		$container->registerService(View::class, function () {
			return new View('');
		}, false);
		$container->registerService('isCLI', function () {
			return \OC::$CLI;
		});




		/** Cron
		$container->registerService('CronService', function ($c) {
			return new CronService(
				$c->query('CredentialService'),
				$c->query('Logger'),
				$c->query('Utils'),
				$c->query('NotificationService'),
				$c->query('ActivityService'),
				$c->query('IDBConnection')
			);
		});**/
		/*
		$container->registerService('Db', function () {
			return new Db();
		});*/



		$container->registerService('Logger', function ($c) {
			return $c->query('ServerContainer')->getLogger();
		});

//		 Aliases for the controllers so we can use the automatic DI
		$container->registerAlias('PageController', PageController::class);
		$container->registerAlias('OwnnoteSharesController', OwnnoteSharesController::class);
		$container->registerAlias('OwnnoteApiController', OwnnoteApiController::class);
		$container->registerAlias('OwnnoteAjaxController', OwnnoteAjaxController::class);

		/*$container->registerAlias('CredentialController', CredentialController::class);
		$container->registerAlias('PageController', PageController::class);
		$container->registerAlias('PageController', PageController::class);
		$container->registerAlias('VaultController', VaultController::class);
		$container->registerAlias('VaultController', VaultController::class);
		$container->registerAlias('CredentialService', CredentialService::class);
		$container->registerAlias('NotificationService', NotificationService::class);
		$container->registerAlias('ActivityService', ActivityService::class);
		$container->registerAlias('VaultService', VaultService::class);
		$container->registerAlias('FileService', FileService::class);
		$container->registerAlias('ShareService', ShareService::class);
		$container->registerAlias('Utils', Utils::class);
		$container->registerAlias('IDBConnection', IDBConnection::class);
		$container->registerAlias('IConfig', IConfig::class);
		$container->registerAlias('SettingsService', SettingsService::class);
		$container->registerAlias('APIMiddleware', APIMiddleware::class);*/
	}

	/**
	 * Register the navigation entry
	 */
	public function registerNavigationEntry() {
		$c = $this->getContainer();
		/** @var \OCP\IServerContainer $server */
		$server = $c->getServer();
		$navigationEntry = function () use ($c, $server) {
			return [
				'id' => $c->getAppName(),
				'order' => 10,
				'name' => $c->query(IL10N::class)->t('Notes'),
				'href' => $server->getURLGenerator()->linkToRoute('ownnote.page.index'),
				'icon' => $server->getURLGenerator()->imagePath($c->getAppName(), 'app.svg'),
			];
		};
		$server->getNavigationManager()->add($navigationEntry);
	}
}