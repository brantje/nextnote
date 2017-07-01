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
	array('name' => 'ownnote_ajax#ajaxsetval', 'url' => '/ajax/v0.2/ajaxsetval', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxgetsharemode', 'url' => '/ajax/v0.2/ajaxgetsharemode', 'verb' => 'GET'),
	array('name' => 'ownnote_api#index', 'url' => '/api/v0.2/ownnote', 'verb' => 'GET'),
	array('name' => 'ownnote_ajax#ajaxindex', 'url' => '/ajax/v0.2/ownnote/ajaxindex', 'verb' => 'GET'),
	array('name' => 'ownnote_api#remoteindex', 'url' => '/api/v0.2/ownnote/remoteindex', 'verb' => 'GET'),
	array('name' => 'ownnote_api#mobileindex', 'url' => '/api/v0.2/ownnote/mobileindex', 'verb' => 'GET'),
	array('name' => 'ownnote_api#announcement', 'url' => '/api/v0.2/ownnote/announcement', 'verb' => 'GET'),
	array('name' => 'ownnote_ajax#ajaxannouncement', 'url' => '/ajax/v0.2/ownnote/ajaxannouncement', 'verb' => 'GET'),
	array('name' => 'ownnote_api#version', 'url' => '/api/v0.2/ownnote/version', 'verb' => 'GET'),
	array('name' => 'ownnote_ajax#ajaxversion', 'url' => '/ajax/v0.2/ownnote/ajaxversion', 'verb' => 'GET'),
	
	array('name' => 'ownnote_api#ren', 'url' => '/api/v0.2/ownnote/ren', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxren', 'url' => '/ajax/v0.2/ownnote/ajaxren', 'verb' => 'POST'),
	array('name' => 'ownnote_api#edit', 'url' => '/api/v0.2/ownnote/edit', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxedit', 'url' => '/ajax/v0.2/ownnote/ajaxedit', 'verb' => 'POST'),
	array('name' => 'ownnote_api#del', 'url' => '/api/v0.2/ownnote/del', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxdel', 'url' => '/ajax/v0.2/ownnote/ajaxdel', 'verb' => 'POST'),
	array('name' => 'ownnote_api#share', 'url' => '/api/v0.2/ownnote/share', 'verb' => 'POST'),
    array('name' => 'ownnote_ajax#ajaxshare', 'url' => '/ajax/v0.2/ownnote/ajaxshare', 'verb' => 'POST'),
	
	array('name' => 'ownnote_shares#getshares', 'url' => '/api/v0.2/ownnote/shares', 'verb' => 'GET'),
	array('name' => 'ownnote_shares#share', 'url' => '/api/v0.2/ownnote/shares', 'verb' => 'POST'),
	array('name' => 'ownnote_shares#unshare', 'url' => '/api/v0.2/ownnote/shares/{itemSource}', 'verb' => 'DELETE', 'requirements' => array('itemSource' => '.+')),
	array('name' => 'ownnote_shares#setpermissions', 'url' => '/api/v0.2/ownnote/shares/{itemSource}/permissions', 'verb' => 'PUT', 'requirements' => array('itemSource' => '.+')),
	
	array('name' => 'ownnote_api#save', 'url' => '/api/v0.2/ownnote/save', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxsave', 'url' => '/ajax/v0.2/ownnote/ajaxsave', 'verb' => 'POST'),
	array('name' => 'ownnote_api#create', 'url' => '/api/v0.2/ownnote/create', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxcreate', 'url' => '/ajax/v0.2/ownnote/ajaxcreate', 'verb' => 'POST'),
	array('name' => 'ownnote_api#delgroup', 'url' => '/api/v0.2/ownnote/delgroup', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxdelgroup', 'url' => '/ajax/v0.2/ownnote/ajaxdelgroup', 'verb' => 'POST'),
	array('name' => 'ownnote_api#rengroup', 'url' => '/api/v0.2/ownnote/rengroup', 'verb' => 'POST'),
	array('name' => 'ownnote_ajax#ajaxrengroup', 'url' => '/ajax/v0.2/ownnote/ajaxrengroup', 'verb' => 'POST'),
    array('name' => 'ownnote_api#preflighted_cors', 'url' => '/api/v0.2/{path}', 'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')),
)));
