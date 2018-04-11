<?php
/**
 * Nextcloud - namespace OCA\Nextnote
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 *
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

namespace OCA\NextNote\Service;

use OCA\NextNote\Db\Notebook;
use OCA\NextNote\Db\NotebookMapper;
use OCA\NextNote\ShareBackend\NextNoteShareBackend;
use OCA\NextNote\Utility\Utils;
use OCP\AppFramework\Db\Entity;


class NotebookService {

	private $groupMapper;
	private $utils;
	private $sharing;

	public function __construct(NotebookMapper $groupMapper, Utils $utils, NextNoteShareBackend $shareBackend) {
		$this->groupMapper = $groupMapper;
		$this->utils = $utils;
		$this->sharing = $shareBackend;
	}

	/**
	 * Find a group by id
	 *
	 * @param null|int $notebook_id
	 * @param null $user_id
	 * @param int|bool $deleted
	 * @return Notebook[]|Notebook
	 */
	public function find($notebook_id=null, $user_id = null, $deleted = false) {
		return $this->groupMapper->find($notebook_id, $user_id, $deleted);
	}
	/**
	 * Find a group by name
	 *
	 * @param $group_name string
	 * @param null $user_id
	 * @param bool $deleted
	 * @return Notebook[]|Notebook
	 */
	public function findByName($group_name=null, $user_id = null, $deleted = false) {
		return $this->groupMapper->findByName($group_name, $user_id, $deleted);
	}

	/**
	 * Creates a group
	 *
	 * @param array|Notebook $group
	 * @param $userId
	 * @return Notebook|Entity
	 * @throws \Exception
	 */
	public function create($group, $userId) {
		if (is_array($group)) {
			$entity = new Notebook();
			$entity->setName($group['name']);
			$entity->setParentId($group['parent_id']);
			$entity->setUid($userId);
			$entity->setGuid($group['guid']);
			$entity->setColor($group['color']);
			$group = $entity;
		}
		if (!$group instanceof Notebook) {
			throw new \Exception("Expected Note object!");
		}
		return $this->groupMapper->insert($group);
	}

	/**
	 * Update a group
	 *
	 * @param $group array|Notebook
	 * @return Notebook|Entity|bool
	 * @throws \Exception
	 * @internal param $userId
	 * @internal param $vault
	 */
	public function update($group) {

		if (is_array($group)) {
			$entity = $this->find($group['id']);
			$entity->setName($group['title']);
			$entity->setParentId($group['parent_id']);
			$entity->setColor($group['color']);
			$group = $entity;
		}

		if (!$group instanceof Notebook) {
			throw new \Exception("Expected Note object!");
		}

		return $this->groupMapper->update($group);
	}

	/**
	 * Delete a group
	 *
	 * @param $group_id
	 * @param string $user_id
	 * @return bool
	 */
	public function delete($group_id, $user_id = null) {
		if (!$this->checkPermissions()) {
			return false;
		}

		$group = $this->groupMapper->find($group_id, $user_id);
		if ($group instanceof Notebook) {
			$this->groupMapper->delete($group);
			return true;
		} else {
			return false;
		}
	}

	private function checkPermissions() {
		return true;
	}
}
