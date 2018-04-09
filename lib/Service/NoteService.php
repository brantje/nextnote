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

namespace OCA\NextNote\Service;

use OCA\NextNote\Db\Notebook;
use OCA\NextNote\Db\Note;
use OCA\NextNote\ShareBackend\NextNoteShareBackend;
use OCA\NextNote\Utility\Utils;
use OCA\NextNote\Db\NoteMapper;


class NoteService {

	private $noteMapper;
	private $utils;
	private $sharing;
	private $groupService;

	public function __construct(NoteMapper $noteMapper, Utils $utils, NextNoteShareBackend $shareBackend, NotebookService $groupService) {
		$this->noteMapper = $noteMapper;
		$this->utils = $utils;
		$this->sharing = $shareBackend;
		$this->groupService = $groupService;
	}

	/**
	 * Get vaults from a user.
	 *
	 * @param $userId
	 * @param int|bool $deleted
	 * @param string|bool $grouping
	 * @return Note[]
	 */
	public function findNotesFromUser($userId, $deleted = false, $grouping = false) {
		// Get shares

		$dbNotes = $this->noteMapper->findNotesFromUser($userId, $deleted, $grouping);
		$sharedNotes = $this->sharing->getSharedNotes();
		$notes = array_merge($dbNotes, $sharedNotes);
		return $notes;
	}

	/**
	 * Get a single vault
	 *
	 * @param $note_id
	 * @param $user_id
	 * @param bool|int $deleted
	 * @return Note
	 * @internal param $vault_id
	 */
	public function find($note_id, $user_id = null, $deleted = false) {
		$note = $this->noteMapper->find($note_id, $user_id, $deleted);
		return $note;
	}

	/**
	 * Creates a note
	 *
	 * @param Note $note
	 * @return Note
	 * @throws \Exception
	 */
	public function create(Note $note) {
		if (!$note instanceof Note) {
			throw new \Exception("Expected Note object!");
		}

		return $this->noteMapper->create($note);
	}

	/**
	 * Update vault
	 *
	 * @param $note Note
	 * @return Note|bool
	 * @throws \Exception
	 * @internal param $userId
	 * @internal param $vault
	 */
	public function update(Note $note) {
		if (!$note instanceof Note) {
			throw new \Exception("Expected Note object!");
		}

		return $this->noteMapper->updateNote($note);
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
		if ($note instanceof Note) {
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
		throw new \Exception('Calling a deprecated method! (Folder' . $FOLDER . '. Showdel: ' . $showdel . ')');
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
		$shared_note = \OCP\Share::getItemSharedWith('nextnote', $nid, 'populated_shares')[0];
		return $shared_note['permissions'] & $permission;
	}
}
