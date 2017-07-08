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
	 * @ngdoc service
	 * @name NextNotesApp.NoteService
	 * @description
	 * # NoteService
	 * Service in the NextNotesApp.
	 */
	angular.module('NextNotesApp')
		.service('NoteService', ['$rootScope', 'NoteFactory', '$timeout', '$q', function($rootScope, NoteFactory, $timeout, $q) {
			var newNoteTemplate = {
				'title': '',
				'content': '',
				'group': ''
			};

			return {
				newNote: function() {
					return angular.copy(newNoteTemplate);
				},
				getNoteById: function(noteId) {
					noteId = parseInt(noteId);
					var deferred = $q.defer();
					if ($rootScope.notes && $rootScope.notes.hasOwnProperty(noteId)) {
						deferred.resolve(new NoteFactory($rootScope.notes[noteId]));
					} else {
						NoteFactory.get({id: noteId}, function(note) {
							$rootScope.notes[note.id] = note;
							deferred.resolve(note);
						});
					}
					return deferred.promise;
				},
				save: NoteFactory.save,
				update: NoteFactory.update

			};
		}]);
}());
