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
script('nextnote', 'share');
script('nextnote', 'app/controllers/MainCtrl');
script('nextnote', 'app/controllers/NoteListCtrl');
script('nextnote', 'app/controllers/NoteViewCtrl');
script('nextnote', 'app/controllers/NoteEditCtrl');
script('nextnote', 'app/services/NoteService');
script('nextnote', 'app/services/NotebookService');
script('nextnote', 'app/factory/NoteFactory');
script('nextnote', 'app/factory/NotebookFactory');
script('nextnote', 'app/directives/tooltip');
script('nextnote', 'app/filters/noteFilter');
script('nextnote', 'app/filters/objectKeysLength');
script('nextnote', 'app/filters/trusted');
script('nextnote', 'app/filters/strip_html');
/*build-js-end*/


/*
 * Styles
 */
//Core
\OCP\Util::addStyle('core', 'icons');
\OCP\Util::addStyle('files_trashbin', 'trash');


/*build-css-start*/
style('nextnote', 'app');
style('nextnote', 'vendor/font-awesome/font-awesome.min');
/*build-css-end*/
echo '<script nonce="test"> var shareMode = "'. $_['shareMode'] .'"; var app_config = '. json_encode($_['config']) .'</script>';
?>
<input type="hidden" name="nextNonce" id="nextNonce" value="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" />
<div id="app" ng-app="NextNotesApp" ng-controller="MainCtrl">
	<div id="app-navigation" ng-show="sidebar_shown">
		<ul id="grouplist">
			<li class="group" ng-init="add_group = false;">
				<a class="name" role="button" title="All" ng-click="add_group = true;" ng-hide="add_group">+ New notebook</a>
				<div ng-show="add_group" class="add_group_container">
					<input type="text" ng-model="new_group_name" id="new_group_name" placeholder="Enter notebook name">
					<div class="button" ng-click="addNotebook(new_group_name); add_group = false;"><i class="fa fa-check" tooltip="Save"></i> </div>
					<div class="button" ng-click="add_group = false; new_group_name = '';"><i class="fa fa-times" tooltip="Cancel"></i> </div>
				</div>
			</li>
			<li class="group"  ng-click="noteGroupFilter.notebook = 'all'; " ng-class="{'active': noteGroupFilter.notebook === 'all' }">
				<a class="name" role="button" title="All">All</a>
				<span class="utils">
					<a class="icon-rename action edit tooltipped rightwards" group="All" original-title=""></a>
					<a class="icon-delete action delete tooltipped rightwards" group="All" original-title=""></a>
					<span class="action numnotes" ng-show="keys(notes).length - 2 > 0">{{ note_count }}</span>
				</span>
			</li>
			<li class="group"  ng-click="noteGroupFilter.notebook = ''; " ng-class="{'active': noteGroupFilter.notebook === '' }">
				<a class="name" title="Not grouped">Not grouped</a>
				<span class="utils">
					<a class="icon-rename action edit tooltipped rightwards" group="All" original-title=""></a>
					<a class="icon-delete action delete tooltipped rightwards" group="All" original-title=""></a>
					<!-- <span class="action numnotes" ng-show="keys(notes).length - 2 > 0">{{ keys(notes).length - 2 }}</span> -->
				</span>
			</li>
			<li id="group-{{group}}" ng-if="group.name !== ''" class="group" ng-click="noteGroupFilter.notebook = group.id; "
				ng-class="{'active': noteGroupFilter.notebook === group.id }" data-type="category" ng-repeat="group in note_groups">
				<a editable-text="group" e-form="textBtnForm" onbeforesave="renameGroup(group, $data)" class="name" id="link-webstore" role="button" title="webstore">{{ group.name }}</a>

				<span class="utils">
					<a ng-click="textBtnForm.$show()" ng-hide="textBtnForm.$visible" class="icon-rename action edit rightwards"></a>
					<a class="icon-delete action delete rightwards" ng-hide="textBtnForm.$visible"></a>
					<span class="action numnotes">{{ group.note_count }}</span>
				</span>
			</li>
			<li data-id="trashbin" class="nav-trashbin" ng-class="{'active': list_filter.deleted === 1}" ng-click="list_filter.deleted = (list_filter.deleted === 0 ) ? 1 : 0; noteGroupFilter.notebook = 'all';">
				<a class="nav-icon-trashbin svg">
					Deleted notes
				</a>
			</li>
		</ul>
	</div>

	<div id="app-content">
		<div ng-controller="NoteListCtrl">
			<div ng-include="'views/list.html'"></div>
		</div>
	</div>
</div>
