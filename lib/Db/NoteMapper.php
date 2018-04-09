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

class NoteMapper extends Mapper {
	private $utils;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'nextnote_notes');
		$this->utils = $utils;
	}


    /**
     * @param $note_id
     * @param null $user_id
     * @param int|bool $deleted
     * @return Note if not found
     */
	public function find($note_id, $user_id = null, $deleted = false) {
		$params = [$note_id];
		$uidSql = '';
		if ($user_id) {
			$params[] = $user_id;
			$uidSql = 'and n.uid = ?';
		}

		$deletedSql = '';
		if ($deleted !== false) {
			$params[] = $deleted;
			$deletedSql = 'and n.deleted = ?';
		}
		$sql = "SELECT n.*, g.name as grouping FROM *PREFIX*nextnote_notes n JOIN oc_nextnote_groups g ON g.id WHERE n.id= ? $uidSql $deletedSql";
		$results = [];
		foreach ($this->execute($sql, $params)->fetchAll() as $item) {
			/**
			 * @var $note Note
			 */
			$note = $this->makeEntityFromDBResult($item);
			/* fetch note parts */
			$noteParts = $this->getNoteParts($note);
			$partsTxt = implode('', array_map(function ($part) {
				return $part['note'];
			}, $noteParts));
			$note->setNote($item['note'] . $partsTxt);

			$results[] = $note;
		}
		return array_shift($results);
	}


	/**
	 * @param $userId
	 * @param int|bool $deleted
	 * @param string|bool $group
	 * @return Note[] if not found
	 */
	public function findNotesFromUser($userId, $deleted = 0, $group = false) {
		$params = [$userId];
		$groupSql = '';
		if ($group) {
			$groupSql = 'and n.grouping = ?';
			$params[] = $group;
		}
		$deletedSql = '';
		if ($deleted !== false) {
			$deleted = (int) $deleted;
			$deletedSql = 'and n.deleted = ?';
			$params[] = $deleted;
		}
		$sql = "SELECT n.*, g.name as grouping FROM *PREFIX*nextnote_notes n JOIN oc_nextnote_groups g ON g.id WHERE n.uid = ? $groupSql $deletedSql";
		$results = [];
		foreach ($this->execute($sql, $params)->fetchAll() as $item) {
			/**
			 * @var $note Note
			 */
			$note = $this->makeEntityFromDBResult($item);
			$note->setNote($item['note']);
			$results[] = $note;
		}
		return $results;
	}

	

	/**
	 * Creates a note
	 *
	 * @param Note $note
	 * @return Note|Entity
	 * @internal param $userId
	 */
	public function create($note) {
		$len = mb_strlen($note->getNote());
		$parts = false;
		if ($len > Utils::$maxPartSize) {
			$parts = $this->utils->splitContent($note->getNote());
			$note->setNote('');
		}
		$note->setShared(false);
		/**
		 * @var $note Note
		 */

		$note = parent::insert($note);

		if ($parts) {
			foreach ($parts as $part) {
				$this->createNotePart($note, $part);
			}
			$note->setNote(implode('', $parts));
		}


		return $note;
	}

	/**
	 * Update note
	 *
	 * @param Note $note
	 * @return Note|Entity
	 */
	public function updateNote($note) {
		$len = mb_strlen($note->getNote());
		$parts = false;
		$this->deleteNoteParts($note);

		if ($len > Utils::$maxPartSize) {
			$parts = $this->utils->splitContent($note->getNote());
			$note->setNote('');
		}
		/**
		 * @var $note Note
		 */
		$note = parent::update($note);
		if ($parts) {
			foreach ($parts as $part) {
				$this->createNotePart($note, $part);
			}
			$note->setNote(implode('', $parts));
		}
		return $note;
	}

	/**
	 * @param Note $note
	 * @param $content
	 */
	public function createNotePart(Note $note, $content) {
		$sql = "INSERT INTO *PREFIX*nextnote_parts VALUES (NULL, ?, ?);";
		$this->execute($sql, array($note->getId(), $content));
	}

	/**
	 * Delete the note parts
	 *
	 * @param Note $note
	 */
	public function deleteNoteParts(Note $note) {
		$sql = 'DELETE FROM *PREFIX*nextnote_parts where id = ?';
		$this->execute($sql, array($note->getId()));
	}

	/**
	 * Get the note parts
	 *
	 * @param Note $note
	 * @return array
	 */
	public function getNoteParts(Note $note) {
		$sql = 'SELECT * from *PREFIX*nextnote_parts where id = ?';
		return $this->execute($sql, array($note->getId()))->fetchAll();
	}

	/**
	 * @param Note $note
	 * @return bool
	 */
	public function deleteNote(Note $note) {
		$this->deleteNoteParts($note);
		parent::delete($note);
		return true;
	}

	/**
	 * @param $arr
	 * @return Note
	 */
	public function makeEntityFromDBResult($arr) {

		$note = new Note();
		$note->setId($arr['id']);
		$note->setName($arr['name']);
		$note->setGuid($arr['guid']);
		$note->setGrouping($arr['grouping']);
		$note->setMtime($arr['mtime']);
		$note->setDeleted($arr['deleted']);
		$note->setUid($arr['uid']);
		return $note;
	}
}
