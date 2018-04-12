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

	private $notebookMapper;
	private $utils;
	private $sharing;

	public function __construct(NotebookMapper $notebookMapper, Utils $utils, NextNoteShareBackend $shareBackend) {
		$this->notebookMapper = $notebookMapper;
		$this->utils = $utils;
		$this->sharing = $shareBackend;
	}

	/**
	 * Find a notebook by id
	 *
	 * @param null|int $notebook_id
	 * @param null $user_id
	 * @param int|bool $deleted
	 * @return Notebook[]|Notebook
	 */
	public function find($notebook_id=null, $user_id = null, $deleted = false) {
		return $this->notebookMapper->find($notebook_id, $user_id, $deleted);
	}
	/**
	 * Find a notebook by name
	 *
	 * @param $notebook_name string
	 * @param null $user_id
	 * @param bool $deleted
	 * @return Notebook[]|Notebook
	 */
	public function findByName($notebook_name=null, $user_id = null, $deleted = false) {
		return $this->notebookMapper->findByName($notebook_name, $user_id, $deleted);
	}

	/**
	 * Creates a notebook
	 *
	 * @param array|Notebook $notebook
	 * @param $userId
	 * @return Notebook|Entity
	 * @throws \Exception
	 */
	public function create($notebook, $userId) {
		if (is_array($notebook)) {
			$entity = new Notebook();
			$entity->setName($notebook['name']);
			$entity->setParentId($notebook['parent_id']);
			$entity->setUid($userId);
			$entity->setGuid($notebook['guid']);
			$entity->setColor($notebook['color']);
			$notebook = $entity;
		}
		if (!$notebook instanceof Notebook) {
			throw new \Exception("Expected Notebook object!");
		}
		return $this->notebookMapper->insert($notebook);
	}

	/**
	 * Update a notebook
	 *
	 * @param $notebook array|Notebook
	 * @return Notebook|Entity|bool
	 * @throws \Exception
	 * @internal param $userId
	 * @internal param $vault
	 */
	public function update($notebook) {

		if (is_array($notebook)) {
			$entity = $this->find($notebook['id']);
			$entity->setName($notebook['title']);
			$entity->setParentId($notebook['parent_id']);
			$entity->setColor($notebook['color']);
			$notebook = $entity;
		}

		if (!$notebook instanceof Notebook) {
			throw new \Exception("Expected Notebook object!");
		}

		return $this->notebookMapper->update($notebook);
	}

	/**
	 * Delete a notebook
	 *
	 * @param $notebook_id
	 * @param string $user_id
	 * @return bool
	 */
	public function delete($notebook_id, $user_id = null) {
		if (!$this->checkPermissions()) {
			return false;
		}

		$notebook = $this->notebookMapper->find($notebook_id, $user_id);
		if ($notebook instanceof Notebook) {
			$this->notebookMapper->delete($notebook);
			return true;
		} else {
			return false;
		}
	}

	private function checkPermissions() {
		return true;
	}
}
