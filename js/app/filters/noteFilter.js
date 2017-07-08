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

angular.module('NextNotesApp').filter('noteGroupFilter', ['$filter', function ($filter) {
	return function (items, filterBy) {
		var filtered = [];
		if (filterBy.hasOwnProperty('grouping') && filterBy.grouping === 'all') {
			return items;
		}
		if (filterBy.hasOwnProperty('grouping') && filterBy.grouping === '') {
			angular.forEach(items, function (item) {
				if (item.grouping === '') {
					filtered.push(item);
				}
			});
			return filtered;
		}

		return $filter('filter')(items, {grouping: filterBy.grouping});
	};
}]);
