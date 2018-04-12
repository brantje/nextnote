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
	 * @ngdoc function
	 * @name NextNotesApp.controller:MainCtrl
	 * @description
	 * # MainCtrl
	 * Controller of the NextNotesApp
	 */
	angular.module('NextNotesApp')
		.controller('MainCtrl', ['$scope','NotebookFactory', '$rootScope', function ($scope, NotebookFactory, $rootScope) {
			$scope.renameGroup = function (oldName, newName) {
				console.log('Rename', oldName, 'to ', newName);
			};

			$scope.addNotebook = function (name) {
				NotebookFactory.save({
					name: name,
					color: '',
					parent_id: 0
				}).$promise.then(function () {
					$rootScope.$emit('refresh_notes');
					OC.Notification.showTemporary('Notebook created');
				});
			};
			$scope.obj_length = function (obj) {
				if(!obj){
					return 0;
				}
				return Object.keys(obj).length;
			};

			$scope.count_empty_groups = function (notes) {
				var counter = 0;
				angular.forEach(notes, function (note) {
					if(note.hasOwnProperty('id') && note.notebook === null){
						counter++;
					}
				});
				return counter;
			};

			$scope.count_deleted_notes = function (notes) {
				var counter = 0;
				angular.forEach(notes, function (note) {
					if(note.hasOwnProperty('id') && note.deleted === 1){
						counter++;
					}
				});
				return counter;
			};

			$scope.hasPermission = function (note, perm) {
				if (note.hasOwnProperty('owner') && note.owner.hasOwnProperty('uid')) {
					if (note && note.owner.uid === OC.currentUser) {
						return true;
					}
					var permission = 'PERMISSION_' + perm.toUpperCase();

					return note.permissions & OC[permission];
				}

			};

		}]);

}());
