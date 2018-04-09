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

angular.module('NextNotesApp').
    factory('NotebookFactory', function($resource, $http) {
      var group = $resource(
          OC.generateUrl('apps/nextnote/api/v2.0/notebook') + '/:id', {id: '@id'}, {
            query: {
              responseType: 'json',
              transformResponse: function(result) {
                var groups = {};
                for (var k in result) {
                  if (result.hasOwnProperty(k) && !isNaN(k)) {
                    var group = result[k];
                    groups[group.id] = group;
                  }
                }
                return groups;
              }
            },
            update: {
              method: 'PUT'
            },
            create: {
              method: 'POST'
            }
          });

      group.prototype.$save = function() {
        if (this.id) {
          return this.$update();
        } else {
          return this.$create();
        }
      };

      group.prototype.$softDelete = function() {
        this.deleted = 1;
        return this.$update();
      };

      group.prototype.$restore = function() {
        this.deleted = 0;
        return this.$update();
      };

      return group;
    });
