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
namespace OCA\OwnNote\AppInfo;

use \OCP\AppFramework\App;
use \OCP\IContainer;

use \OCA\OwnNote\Controller\PageController;
use \OCA\OwnNote\Controller\OwnnoteApiController;
use \OCA\OwnNote\Controller\OwnnoteAjaxController;

class Application extends App {

	public function __construct(array $urlParams = array()) {
		parent::__construct ( 'ownnote', $urlParams );
		
		$container = $this->getContainer ();
		
		/**
		 * Controllers
		 */
		$container->registerService ( 'PageController', function (IContainer $c) {
			return new PageController ( $c->query ( 'AppName' ), $c->query ( 'Request' ), $c->query ( 'UserId' ) );
		} );
		
		$container->registerService ( 'OwnnoteApiController', function ($c) {
			return new OwnnoteApiController ( $c->query ( 'AppName' ), $c->query ( 'Request' ), $c->query ( 'UserManager' ), $c->query ( 'Logger' ) );
		} );
		
		$container->registerService ( 'OwnnoteAjaxController', function ($c) {
			return new OwnnoteAjaxController ( $c->query ( 'AppName' ), $c->query ( 'Request' ), $c->query ( 'UserManager' ), $c->query ( 'Logger' ) );
		} );
		
		/**
		 * Core
		 */
		$container->registerService ( 'UserId', function (IContainer $c) {
			return \OCP\User::getUser ();
		} );
		
		$container->registerService ( 'UserManager', function ($c) {
			return $c->query ( 'ServerContainer' )->getUserManager ();
		} );
		
		$container->registerService ( 'Logger', function ($c) {
			return $c->query ( 'ServerContainer' )->getLogger ();
		} );
	}
}
