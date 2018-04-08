<?php
/**
 * Nextcloud - NextNote
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

namespace OCA\NextNote\AppInfo;

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$application = new Application();

$application->registerRoutes($this, array('routes' => array(

	array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),


	// V2.0 API
	array('name' => 'note_api#preflighted_cors', 'url' => '/api/v2.0/{path}', 'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')),

	//Notes
	array('name' => 'note_api#index', 'url' => '/api/v2.0/note', 'verb' => 'GET'),
	array('name' => 'note_api#create', 'url' => '/api/v2.0/note', 'verb' => 'POST'),
	array('name' => 'note_api#get', 'url' => '/api/v2.0/note/{id}', 'verb' => 'GET'),
	array('name' => 'note_api#update', 'url' => '/api/v2.0/note/{id}', 'verb' => 'PUT'),
	array('name' => 'note_api#delete', 'url' => '/api/v2.0/note/{id}', 'verb' => 'DELETE'),


	array('name' => 'settings#saveAdminSetting', 'url' => '/api/v2.0/settings', 'verb' => 'POST'),
	array('name' => 'settings#saveUserSetting', 'url' => '/api/v2.0/settings-user', 'verb' => 'POST'),
	array('name' => 'settings#getSettings', 'url' => '/api/v2.0/settings', 'verb' => 'GET'),

	//Sharing
	array('name' => 'share_api#getshares', 'url' => '/api/v2.0/sharing/shares', 'verb' => 'GET'),
	array('name' => 'share_api#share', 'url' => '/api/v2.0/sharing/shares', 'verb' => 'POST'),
	array('name' => 'share_api#unshare', 'url' => '/api/v2.0/sharing/shares/{itemSource}', 'verb' => 'DELETE', 'requirements' => array('itemSource' => '.+')),
	array('name' => 'share_api#setpermissions', 'url' => '/api/v2.0/sharing/shares/{itemSource}/permissions', 'verb' => 'PUT', 'requirements' => array('itemSource' => '.+')),



)));
