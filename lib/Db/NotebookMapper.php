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


class NotebookMapper extends Mapper {
	private $utils;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'nextnote_groups');
		$this->utils = $utils;
	}

	/**
	 * Get Notebook(s)
	 * @param null|int $notebook_id
	 * @param null|int $user_id
	 * @param bool|int $deleted
	 * @return Notebook[]|Notebook
	 */
	public function find($notebook_id = null, $user_id = null, $deleted = false) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('g.*', 'g.guid as guid', $qb->createFunction('COUNT(n.id) as note_count')) //'COUNT(n.id) as note_count'
			->from('nextnote_groups', 'g')
			->leftJoin('g','nextnote_notes','n',  $qb->expr()->eq('g.id', 'n.notebook'))->groupBy(['g.id']);

		$where = [];
		if(!is_null($notebook_id)){
			$where['g.id'] = $notebook_id;
		}

		if ($user_id !== null) {
			$where['g.uid'] = $user_id;
		}

		if ($deleted !== false) {
			$where['g.deleted'] = $deleted;
		}
		$i = 0;
		foreach ($where as $field => $value){
			if($i === 0){
				$qb->where($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			} else {
				$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			}
			$i++;
		}

		$result = $qb->execute();

		/**
		 * @var $results Notebook[]
		 */
		$results = [];
		while ($item = $result->fetch()) {
			$results[] = $this->makeEntityFromDBResult($item);
		}
		$result->closeCursor();
		return $results;
	}

	/**
	 * @param $group_name
	 * @param null $user_id
	 * @param bool $deleted
	 * @return Notebook[]|Notebook
	 */
	public function findByName($group_name, $user_id = null, $deleted = false) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('g.*', 'g.guid as guid', $qb->createFunction('COUNT(n.id) as note_count')) //'COUNT(n.id) as note_count'
		->from('nextnote_groups', 'g')
			->leftJoin('g','nextnote_notes','n',  $qb->expr()->eq('g.id', 'n.notebook'))->groupBy(['g.id']);

		$where = [];
		if($group_name){
			$where['g.name'] = $group_name;
		}

		if ($user_id !== null) {
			$where['g.uid'] = $user_id;
		}

		if ($deleted !== false) {
			$where['g.deleted'] = $deleted;
		}
		$i = 0;
		foreach ($where as $field => $value){
			if($i === 0){
				$qb->where($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			} else {
				$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			}
			$i++;
		}

		$result = $qb->execute();

		/**
		 * @var $results Notebook[]
		 */
		$results = [];
		while ($item = $result->fetch()) {
			$results[] = $this->makeEntityFromDBResult($item);
		}
		$result->closeCursor();
		return $results;
	}

   	/**
	 * Creates a group
	 *
	 * @param Notebook|Entity $group
	 * @return Note|Entity
	 * @internal param $userId
	 */
	public function insert(Entity $group) {
		$group->setNoteCount(null);
		return parent::insert($group);
	}

	/**
	 * Update group
	 *
	 * @param Notebook|Entity $group
	 * @return Notebook|Entity
	 */
	public function update(Entity $group) {
		$group->setNoteCount(null);
		return parent::update($group);
	}

	/**
	 * Delete group
	 *
	 * @param Notebook|Entity $group
	 * @return Notebook|Entity
	 */
	public function delete(Entity $group) {
		return parent::delete($group);
	}

	/**
	 * @param $arr
	 * @return Notebook
	 */
	public function makeEntityFromDBResult($arr) {
		$group = new Notebook();
		$group->setId($arr['id']);
		$group->setName($arr['name']);
		$group->setGuid($arr['guid']);
		$group->setParentId($arr['parent_id']);
		$group->setColor($arr['color']);
		$group->setNoteCount((int) $arr['note_count']);
		$group->setDeleted($arr['deleted']);

		return $group;
	}


}
