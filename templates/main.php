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
/*build-js-start*/
script('nextnote', 'vendor/tinymce/tinymce.min');
script('nextnote', 'lib/tinymceNextcloudFileBrowser');
script('nextnote', 'vendor/angular/angular.min');
script('nextnote', 'vendor/angular-animate/angular-animate');
script('nextnote', 'vendor/angular-cookies/angular-cookies');
script('nextnote', 'vendor/angular-resource/angular-resource');
script('nextnote', 'vendor/angular-route/angular-route');
script('nextnote', 'vendor/angular-sanitize/angular-sanitize');
script('nextnote', 'vendor/angular-touch/angular-touch');
script('nextnote', 'vendor/angular-tinymce/angular-tinymce');
script('nextnote', 'vendor/angular-timeago/angular-timeago-core');
script('nextnote', 'vendor/angular-timeago/angular-timeago');
script('nextnote', 'vendor/angular-xeditable/xeditable');

script('nextnote', 'app/app');
script('nextnote', 'app/routes');
script('nextnote', 'templates');
script('nextnote', 'app/controllers/MainCtrl');
script('nextnote', 'app/controllers/NoteListCtrl');
script('nextnote', 'app/controllers/NoteEditCtrl');
script('nextnote', 'app/services/NoteService');
script('nextnote', 'app/factory/NoteFactory');
script('nextnote', 'app/filters/noteFilter');
/*build-js-end*/


/*
 * Styles
 */
//Core
\OCP\Util::addStyle('core', 'icons');
\OCP\Util::addStyle('files_trashbin', 'trash');


/*build-css-start*/
style('nextnote', 'app');
/*build-css-end*/
$sharemode = \OCP\Config::getAppValue('nextnote', 'sharemode', 'merge');
echo '<script nonce="test"> var shareMode = "'. $sharemode .'"</script>';
?>
<input type="hidden" name="nextNonce" id="nextNonce" value="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" />
<div id="app" ng-app="NextNotesApp" ng-controller="MainCtrl">
	<div id="app-navigation">
		<ul id="grouplist">
			<li class="group"  ng-click="noteGroupFilter.grouping = 'all'; " ng-class="{'active': noteGroupFilter.grouping === 'all' }">
				<a class="name" role="button" title="All">All</a>
				<span class="utils">
					<a class="icon-rename action edit tooltipped rightwards" group="All" original-title=""></a>
					<a class="icon-delete action delete tooltipped rightwards" group="All" original-title=""></a>
					<span class="action numnotes" ng-show="keys(notes).length - 2 > 0">{{ keys(notes).length - 2 }}</span>
				</span>
			</li>
			<li class="group"  ng-click="noteGroupFilter.grouping = ''; " ng-class="{'active': noteGroupFilter.grouping === '' }">
				<a class="name" title="Not grouped">Not grouped</a>
				<span class="utils">
					<a class="icon-rename action edit tooltipped rightwards" group="All" original-title=""></a>
					<a class="icon-delete action delete tooltipped rightwards" group="All" original-title=""></a>
					<!-- <span class="action numnotes" ng-show="keys(notes).length - 2 > 0">{{ keys(notes).length - 2 }}</span> -->
				</span>
			</li>
			<li id="group-{{group}}" ng-if="group !== ''" class="group" ng-click="noteGroupFilter.grouping = group; " ng-class="{'active': noteGroupFilter.grouping === group }" data-type="category" ng-repeat="group in note_groups">
				<a editable-text="group" e-form="textBtnForm" onbeforesave="renameGroup(group, $data)" class="name" id="link-webstore" role="button" title="webstore">{{ group }}</a>

				<span class="utils">
					<a ng-click="textBtnForm.$show()" ng-hide="textBtnForm.$visible" class="icon-rename action edit rightwards"></a>
					<a class="icon-delete action delete rightwards" ng-hide="textBtnForm.$visible"></a>
					<!-- <span class="action numnotes">1</span> -->
				</span>
			</li>
			<li data-id="trashbin" class="nav-trashbin" ng-class="{'active': list_filter.deleted === 1}" ng-click="list_filter.deleted = (list_filter.deleted === 0 ) ? 1 : 0">
				<a class="nav-icon-trashbin svg">
					Deleted notes
				</a>
			</li>
		</ul>
	</div>

	<div id="app-content">
		<div id="app-navigation-toggle" class="icon-menu" style="display:none;"></div>
		<ng-view></ng-view>
	</div>
</div>
