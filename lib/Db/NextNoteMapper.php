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

class NextNoteMapper extends Mapper {
	private $utils;
	private $maxNoteFieldLength = 2621440;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'ownnote');
		$this->utils = $utils;
	}


    /**
     * @param $note_id
     * @param null $user_id
     * @param int|bool $deleted
     * @return NextNote if not found
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
		$sql = "SELECT id, uid, name, grouping, shared, mtime, deleted, note FROM *PREFIX*ownnote n WHERE n.id= ? $uidSql $deletedSql";
		$results = [];
		foreach ($this->execute($sql, $params)->fetchAll() as $item) {
			/**
			 * @var $note NextNote
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
	 * @return NextNote[] if not found
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
		$sql = "SELECT id, uid, name, grouping, shared, mtime, deleted, note FROM *PREFIX*ownnote n WHERE `uid` = ? $groupSql $deletedSql";
		$results = [];
		foreach ($this->execute($sql, $params)->fetchAll() as $item) {
			/**
			 * @var $note NextNote
			 */
			$note = $this->makeEntityFromDBResult($item);
			$results[] = $note;
		}
		return $results;
	}

	

	/**
	 * Creates a note
	 *
	 * @param NextNote $note
	 * @return NextNote|Entity
	 * @internal param $userId
	 */
	public function create($note) {
		$len = mb_strlen($note->getNote());
		$parts = false;
		if ($len > $this->maxNoteFieldLength) {
			$parts = $this->utils->splitContent($note->getNote());
			$note->setNote('');
		}

		$note->setShared(false);
		/**
		 * @var $note NextNote
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
	 * @param NextNote $note
	 * @return NextNote|Entity
	 */
	public function updateNote($note) {
		$len = mb_strlen($note->getNote());
		$parts = false;
		$this->deleteNoteParts($note);

		if ($len > $this->maxNoteFieldLength) {
			$parts = $this->utils->splitContent($note->getNote());
			$note->setNote('');
		}
		/**
		 * @var $note NextNote
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
	 * @param NextNote $note
	 * @param $content
	 */
	public function createNotePart(NextNote $note, $content) {
		$sql = "INSERT INTO *PREFIX*ownnote_parts VALUES (NULL, ?, ?);";
		$this->execute($sql, array($note->getId(), $content));
	}

	/**
	 * Delete the note parts
	 *
	 * @param NextNote $note
	 */
	public function deleteNoteParts(NextNote $note) {
		$sql = 'DELETE FROM *PREFIX*ownnote_parts where id = ?';
		$this->execute($sql, array($note->getId()));
	}

	/**
	 * Get the note parts
	 *
	 * @param NextNote $note
	 * @return array
	 */
	public function getNoteParts(NextNote $note) {
		$sql = 'SELECT * from *PREFIX*ownnote_parts where id = ?';
		return $this->execute($sql, array($note->getId()))->fetchAll();
	}

	/**
	 * @param NextNote $note
	 * @return bool
	 */
	public function deleteNote(NextNote $note) {
		$this->deleteNoteParts($note);
		parent::delete($note);
		return true;
	}

	/**
	 * @param $arr
	 * @return NextNote
	 */
	public function makeEntityFromDBResult($arr) {
		$note = new NextNote();
		$note->setId($arr['id']);
		$note->setName($arr['name']);
		$note->setGrouping($arr['grouping']);
		$note->setMtime($arr['mtime']);
		$note->setDeleted($arr['deleted']);
		$note->setUid($arr['uid']);
		return $note;
	}
}
