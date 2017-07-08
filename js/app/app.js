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

(function() {
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
			'xeditable'
		])
		.config(['$httpProvider', function($httpProvider) {
		/** global: oc_requesttoken */
		$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;
	}]).config(['$qProvider', function($qProvider) {
		$qProvider.errorOnUnhandledRejections(false);
	}]).run(['$rootScope', 'NoteFactory', 'editableOptions', function($rootScope, NoteFactory, editableOptions) {
		editableOptions.theme = 'bs2';
		console.log('App loaded');
		$rootScope.list_sorting = {
			what: 'mtime',
			reverse: true
		};
		$rootScope.noteGroupFilter = {
			grouping: 'all'
		};
		$rootScope.list_filter = {
			deleted: 0
		};
		function loadNotes() {
            NoteFactory.query(function(notes) {
                console.log('Notes received', notes);
                $rootScope.notes = notes;
                $rootScope.$broadcast('nextnotes_notes_loaded');
                $rootScope.keys = Object.keys;

                // Fix nextcloud's behaviour because templates are injected with JS.
                $rootScope.$on('$viewContentLoaded', function() {
                    $(window).trigger('resize');
                });
                $(window).trigger('resize');


                //Setup locale data
                $rootScope.dateFormat = moment.localeData().longDateFormat('L').replace(/D/g, 'd').replace(/Y/g, 'y');
                $rootScope.dateFormatLong = moment.localeData().longDateFormat('L').replace(/D/g, 'd').replace(/Y/g, 'y') + ' H:mm';
            });
        }
        loadNotes();
		$rootScope.$on('refresh_notes', function() {
            loadNotes();
        });


		// Setup a watcher on the notes so groups are always correct
		// @TODO Implement multi level support


    var getGroupIndexByName = function(groupName) {
      for(var i = 0; i < $rootScope.note_groups.length; i++){
        if(groupName === $rootScope.note_groups[i].name){
          return i;
        }
      }
      return -1;
    };

		$rootScope.$watch('[notes, list_filter]', function(n) {
			if (!n) {
				return;
			}

      $rootScope.note_groups = [];
      $rootScope.note_count = 0;

			var notes = $rootScope.notes;
			angular.forEach(notes, function(note) {
				if (note.hasOwnProperty('id')) {
					if(note.deleted !== $rootScope.list_filter.deleted){
						return;
					}
          $rootScope.note_count++;
					var idx = getGroupIndexByName(note.grouping);
					if (shareMode === 'merge' && idx === -1 && note.grouping !== '_new' && note.grouping !== '') {
						$rootScope.note_groups.push({
							name: note.grouping,
							note_count: 1
						});
					}
					if (shareMode === 'merge' && idx !== -1 && note.grouping !== '_new' && note.grouping !== '') {
            $rootScope.note_groups[idx].note_count++;
					}
				}
			});
		} , true);
	}]);
}());
