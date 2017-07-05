<?php
/**
 * Nextcloud - namespace OCA\Nextnote
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

namespace OCA\OwnNote\Service;

use OCA\OwnNote\Db\OwnNote;
use OCA\OwnNote\Utility\Utils;
use OCA\OwnNote\Db\OwnNoteMapper;


class OwnNoteService {

	private $noteMapper;
	private $utils;

	public function __construct(OwnNoteMapper $noteMapper, Utils $utils) {
		$this->noteMapper = $noteMapper;
		$this->utils = $utils;
	}

	/**
	 * Get vaults from a user.
	 *
	 * @param $userId
	 * @param int $deleted
	 * @return OwnNote[]
	 */
	public function findNotesFromUser($userId, $deleted = 0) {
		// Get shares
		return $this->noteMapper->findNotesFromUser($userId, $deleted);
	}

	/**
	 * Get a single vault
	 *
	 * @param $note_id
	 * @param $user_id
	 * @return OwnNote
	 * @internal param $vault_id
	 */
	public function find($note_id, $user_id = null) {
		$note = $this->noteMapper->find($note_id, $user_id);
		return $note;
	}

	/**
	 * Creates a note
	 *
	 * @param array|OwnNote $note
	 * @param $userId
	 * @return OwnNote
	 * @throws \Exception
	 */
	public function create($note, $userId) {
		if (is_array($note)) {
			$entity = new OwnNote();
			$entity->setName($note['title']);
			$entity->setUid($userId);
			$entity->setGrouping($note['group']);
			$entity->setNote($note['note'] ? $note['note'] : '');
			$entity->setMtime(time());
			$note = $entity;
		}
		if (!$note instanceof OwnNote) {
			throw new \Exception("Expected OwnNote object!");
		}
		return $this->noteMapper->create($note);
	}

	/**
	 * Update vault
	 *
	 * @param $note array|OwnNote
	 * @return OwnNote|bool
	 * @throws \Exception
	 * @internal param $userId
	 * @internal param $vault
	 */
	public function update($note) {

		if (is_array($note)) {
			$entity = $this->find($note['id']);
			$entity->setName($note['name']);
			$entity->setGrouping($note['group']);
			$entity->setNote($note['note']);
			$entity->setMtime(time());
			$note = $entity;
		}
		if (!$note instanceof OwnNote) {
			throw new \Exception("Expected OwnNote object!");
		}

		// @TODO check if we can enable this without issues
//		if (!$this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE, $note->getId())) {
//			return false;
//		}

		return $this->noteMapper->updateNote($note);
	}

	public function renameNote($FOLDER, $id, $in_newname, $in_newgroup, $uid = null) {
		$newname = str_replace("\\", "-", str_replace("/", "-", $in_newname));
		$newgroup = str_replace("\\", "-", str_replace("/", "-", $in_newgroup));

		$note = $this->find($id);
		$note->setName($newname);
		$note->setGrouping($newgroup);
		$this->update($note);

		return true;
	}

	/**
	 * Delete a vault from user
	 *
	 * @param $note_id
	 * @param string $user_id
	 * @return bool
	 * @internal param string $vault_guid
	 */
	public function delete($note_id, $user_id = null) {
		if (!$this->checkPermissions(\OCP\Constants::PERMISSION_DELETE, $note_id)) {
			return false;
		}

		$note = $this->noteMapper->find($note_id, $user_id);
		if ($note instanceof OwnNote) {
			$this->noteMapper->deleteNote($note);
			return true;
		} else {
			return false;
		}
	}


	/**
	 * @param $FOLDER
	 * @param boolean $showdel
	 * @return array
	 * @throws \Exception
	 */
	public function getListing($FOLDER, $showdel) {
		throw new \Exception('Calling a deprecated method! (Folder'. $FOLDER. '. Showdel: '. $showdel .')');
	}

	private function checkPermissions($permission, $nid) {
		// gather information
		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$note = $this->find($nid);
		// owner is allowed to change everything
		if ($uid === $note->getUid()) {
			return true;
		}

		// check share permissions
		$shared_note = \OCP\Share::getItemSharedWith('ownnote', $nid, 'populated_shares')[0];
		return $shared_note['permissions'] & $permission;
	}
}
