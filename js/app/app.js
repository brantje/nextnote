/**
 * Nextcloud - NextNotes
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

(function () {
	'use strict';

	/**
	 * @ngdoc overview
	 * @name passmanApp
	 * @description
	 * # passmanApp
	 *
	 * Main module of the application.
	 */
	angular
		.module('NextNotesApp', [
			'ngAnimate',
			'ngCookies',
			'ngResource',
			'ngRoute',
			'ngSanitize',
			'ngTouch',
			'templates-main',
			'ui.tinymce',
			'yaru22.angular-timeago',
			'xeditable',
			'pascalprecht.translate'
		])
		.config(['$httpProvider', function ($httpProvider) {
			/** global: oc_requesttoken */
			$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;
		}]).config(['$qProvider', function ($qProvider) {
		$qProvider.errorOnUnhandledRejections(false);
	}]).config(function($translateProvider) {
		$translateProvider.useSanitizeValueStrategy('sanitizeParameters');
		$translateProvider.useUrlLoader(OC.generateUrl('/apps/nextnote/api/v2/language'));
		$translateProvider.preferredLanguage('en');
	}).config(function (timeAgoSettings) {
		timeAgoSettings.overrideLang = OC.getLocale()+ '_' + OC.getLocale().toUpperCase();
	}).run(['$rootScope', 'NoteFactory', 'editableOptions', 'NotebookFactory', function ($rootScope, NoteFactory, editableOptions, NotebookFactory) {
		editableOptions.theme = 'bs2';
		console.log('App loaded');
		$rootScope.list_sorting = {
			what: 'name',
			reverse: true
		};
		$rootScope.noteGroupFilter = {
			notebook: 'all'
		};
		$rootScope.list_filter = {
			deleted: 0
		};
		$rootScope.OC = OC;
		$rootScope.sidebar_shown = true;

		$rootScope.$on('show_sidebar', function (evt, state) {
			$rootScope.sidebar_shown = state;
		});

		function loadNotes () {
			NoteFactory.query(function (notes) {
				console.log('Notes received', notes);
				$rootScope.notes = notes;
				$rootScope.$broadcast('nextnotes_notes_loaded');
				$rootScope.keys = Object.keys;

				// Fix nextcloud's behaviour because templates are injected with JS.
				$rootScope.$on('$viewContentLoaded', function () {
					$(window).trigger('resize');
				});
				$(window).trigger('resize');


				//Setup locale data
				$rootScope.dateFormat = moment.localeData().longDateFormat('L').replace(/D/g, 'd').replace(/Y/g, 'y');
				$rootScope.dateFormatLong = moment.localeData().longDateFormat('L').replace(/D/g, 'd').replace(/Y/g, 'y') + ' H:mm';
			});
			NotebookFactory.query(function (groups) {
				console.log('Groups received', groups);
				$rootScope.note_groups = groups;
				$rootScope.$broadcast('nextnotes_notebooks_loaded');
			});
		}

		loadNotes();
		$rootScope.$on('refresh_notes', function () {
			loadNotes();
		});
		$rootScope.app_config = window.app_config;
	}]);
}());
