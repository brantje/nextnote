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
	 * @name passmanApp.controller:MainCtrl
	 * @description
	 * # MainCtrl
	 * Controller of the passmanApp
	 */
	angular.module('NextNotesApp').controller('NoteEditCtrl', [
		'$scope',
		'$rootScope',
		'NoteService',
		'$routeParams',
		'$location',
		'$timeout',
		'NoteFactory',
		'NotebookFactory',
		function ($scope, $rootScope, NoteService, $routeParams, $location, $timeout,
				  NoteFactory, NotebookFactory) {
			$scope.noteShadowCopy = {
				title: '',
				content: '',
				owner: {
					uid: OC.getCurrentUser().uid
				},
				permissions: OC.PERMISSION_ALL
			};

			function saveNote () {
				var isNewNote = ($scope.noteShadowCopy.id === undefined);
				$scope.noteShadowCopy.$save().then( function (result) {
					result.mtime = result.mtime * 1000;
					$rootScope.notes[result.id] = result;
					$scope.autoSaved = true;
					$timeout(function () {
						$scope.autoSaved = false;
					}, 2500);
					if(isNewNote){
						window.location = '#!/note/edit/'+result.id;
					}
					$rootScope.$emit('refresh_notes');
				});
			}

			function initGroups () {
				$scope.local_notebooks = angular.copy($rootScope.note_groups);
				$scope.local_notebooks.empty = {
					id: '',
					name: 'Not grouped'
				};

				$scope.local_notebooks._new = {
					id: '_new',
					name: 'New group'
				};
			}

			$scope.new_group = '';

			function loadNote () {
				var noteId = ($routeParams.noteId) ? $routeParams.noteId : null;
				if (noteId) {
					NoteService.getNoteById(noteId).then(function (note) {
						$scope.note = note;
						$scope.noteShadowCopy = new NoteFactory(angular.copy(note));
					});
				} else {
					$scope.note = NoteService.newNote();
					$scope.noteShadowCopy = new NoteFactory(angular.copy($scope.note));
				}
			}

			if (!$rootScope.note_groups) {
				$rootScope.$on('nextnotes_notebooks_loaded', function () {
					initGroups();
				});
			} else {
				initGroups();
			}


			if ($rootScope.notes) {
				loadNote();
			} else {
				$rootScope.$on('nextnotes_notes_loaded', function () {
					loadNote();
				});
			}

			var o = $('#ownnote').offset();
			var h = $(window).height() - o.top;

			var langMapper = {
				'bg': 'bg_BG',
				'cs': 'cs_CZ',
				'fi': 'fi_FI',
				'hu': 'hu_HU',
				'nb': 'nb_NO',
				'sk': 'sk_SK',
				'th': 'th_TH',
				'ja': 'ja_JP',
				'sv': 'sv_SE',
			};
			var locale = OC.getLocale().replace('-','_');
			if(langMapper.hasOwnProperty(locale)){
				locale = langMapper[locale];
			}

			$scope.tinymceOptions = {
				menubar: false,
				theme: 'modern',
				language: locale,
				skin: 'lightgray',
				plugins: [
					'advlist autolink lists link image charmap print preview hr anchor pagebreak',
					'searchreplace wordcount visualblocks visualchars code fullscreen',
					'insertdatetime media nonbreaking save table contextmenu directionality',
					'emoticons template paste textcolor colorpicker textpattern imagetools codesample toc help bdesk_photo',
				],
				extended_valid_elements: 'form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],textarea[id|name|rows|cols|maxlength|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],option[name|id|value|selected|style],select[id|name|type|value|size|maxlength|checked|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|multiple|style]',
				toolbar1: 'print fullscreen | undo redo | formatselect fontselect fontsizeselect | bold italic strikethrough subscript superscript | emoticons',
				toolbar2: ' forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist table outdent indent | link image bdesk_photo insertdatetime toc | codesample help | code',
				image_advtab: true,
				allow_html_data_urls: true,
				allow_script_urls: true,
				paste_data_images: true,
				width: '100%',
				height: h - 155,
				browser_spellcheck: true,
				autoresize_min_height: h - 140,
				autoresize_max_height: h - 140,
				file_picker_types: 'file image media',
				file_browser_callback: NextCloudFileBrowserDialogue,
				contextmenu: 'print link image inserttable | cell row column deletetable',
				textpattern_patterns: [
					{start: '*', end: '*', format: 'italic'},
					{start: '**', end: '**', format: 'bold'},
					{start: '#', format: 'h1'},
					{start: '##', format: 'h2'},
					{start: '###', format: 'h3'},
					{start: '####', format: 'h4'},
					{start: '#####', format: 'h5'},
					{start: '######', format: 'h6'},
					{start: '1. ', cmd: 'InsertOrderedList'},
					{start: '* ', cmd: 'InsertUnorderedList'},
					{start: '- ', cmd: 'InsertUnorderedList'},
				],
			};

			$scope.autoSaved = false;
			$scope.saveNote = function (autoSave) {
				if (autoSaveTimer) {
					$timeout.cancel(autoSaveTimer);
				}
				if (!$scope.noteShadowCopy.title) {
					return;
				}
				if ($scope.noteShadowCopy.notebook && $scope.noteShadowCopy.notebook.hasOwnProperty('id')) {
					$scope.noteShadowCopy.notebook_id = $scope.noteShadowCopy.notebook.id;
				}

				if ($scope.noteShadowCopy.hasOwnProperty('notebook') && $scope.noteShadowCopy.notebook !== null && $scope.noteShadowCopy.notebook.id === '_new' &&
					$scope.new_group !== '') {

					NotebookFactory.save({
						name: angular.copy($scope.new_group),
						color: '',
						parent_id: 0
					}).$promise.then(function (notebook) {
						$scope.noteShadowCopy.notebook_id = notebook.id;
						saveNote();
					});
				} else {
					saveNote();
				}

			};

			var autoSaveTimer;
			var initialSave = true;


			var watcher = $scope.$watch('[noteShadowCopy.title, noteShadowCopy.content]',
				function () {
					if (autoSaveTimer) {
						$timeout.cancel(autoSaveTimer);
					}
					if (!$scope.hasPermission($scope.noteShadowCopy, 'update')) {
						watcher();
						console.log('Disabling auto save, no edit permissions');
						return;
					}
					if(!$scope.noteShadowCopy.id){
						return;
					}
					if ($scope.noteShadowCopy.title &&
						$scope.noteShadowCopy.title !== '') {
						if (initialSave) {
							initialSave = false;
							return;
						}
						autoSaveTimer = $timeout(function () {
							if ($routeParams.noteId) {
								$scope.saveNote(true);
							}
						}, 1000);
					}
				});

		}]);
}());
