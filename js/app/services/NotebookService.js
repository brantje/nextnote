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
	angular.module('NextNotesApp').service('NotebookService', [
		'$rootScope',
		'NotebookFactory',
		'$timeout',
		'$q',
		function($rootScope, NotebookFactory, $timeout, $q) {
			var newGroupTemplate = {
				'name': '',
				'color': '',
				'parent_id': 0
			};

			return {
				newGroup: function() {
					return angular.copy(newGroupTemplate);
				},
				getGroupById: function(groupId) {
					groupId = parseInt(groupId);
					var deferred = $q.defer();
					NotebookFactory.get({id: groupId}, function(group) {
						$rootScope.notes[group.id] = group;
						deferred.resolve(group);
					});
					return deferred.promise;
				},
				save: function(notebook){
					NotebookFactory.save(notebook)
				},
				update: NotebookFactory.update

			};
		}]);
}());
