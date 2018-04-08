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

namespace OCA\NextNote\Db;

use \OCA\NextNote\Utility\Utils;
use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class GroupMapper extends Mapper {
	private $utils;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'nextnote_groups');
		$this->utils = $utils;
	}


	public function find($id) {
		$params = [$id];
		$sql = "SELECT * from *PREFIX*nextnote_groups where id = ?";
		foreach ($this->execute($sql, $params)->fetchAll() as $item) {
			return $this->makeEntityFromDBResult($item);
		}
	}

   	/**
	 * Creates a group
	 *
	 * @param Group|Entity $note
	 * @return NextNote|Entity
	 * @internal param $userId
	 */
	public function create(Entity $note) {
		return parent::insert($note);
	}

	/**
	 * Update group
	 *
	 * @param Group|Entity $group
	 * @return Group|Entity
	 */
	public function update(Entity $group) {
		return parent::update($group);
	}

	/**
	 * Delete group
	 *
	 * @param Group|Entity $group
	 * @return Group|Entity
	 */
	public function delete(Entity $group) {
		return parent::delete($group);
	}

	/**
	 * @param $arr
	 * @return Group
	 */
	public function makeEntityFromDBResult($arr) {
		$group = new Group();
		$group->setId($arr['id']);
		$group->setName($arr['name']);
		$group->setParentId($arr['parent_id']);
		$group->setColor($group['color']);
		$group->setDeleted($arr['deleted']);

		return $group;
	}

}
