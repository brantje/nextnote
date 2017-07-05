<?php
/**
 * Nextcloud - ownnote
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

namespace OCA\OwnNote\Db;

use \OCA\OwnNote\Utility\Utils;
use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class OwnNoteMapper extends Mapper {
	private $utils;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'ownnote');
		$this->utils = $utils;
	}


	/**
	 * @param $note_id
	 * @param null $user_id
	 * @return OwnNote if not found
	 */
	public function find($note_id, $user_id = null) {
		$params = [$note_id];
		$uidSql = '';
		if($user_id){
			$params[] = $user_id;
			$uidSql = 'and n.uid = ?';
		}
		$sql = "SELECT id, uid, name, grouping, shared, mtime, deleted, note FROM *PREFIX*ownnote n WHERE n.id= ? $uidSql and n.deleted = 0";
		$results = [];
		foreach($this->execute($sql, $params)->fetchAll() as $item){
			/**
			 * @var $note OwnNote
			 */
			$note = $this->makeEntityFromDBResult($item);
			$results[] = $note;
		}
		return array_shift($results);
	}


	/**
	 * @param $userId
	 * @param int $deleted
	 * @return OwnNote[] if not found
	 */
	public function findNotesFromUser($userId, $deleted = 0) {
		$params = [$userId, $deleted];
		$sql = "SELECT id, uid, name, grouping, shared, mtime, deleted, note FROM *PREFIX*ownnote n WHERE `uid` = ? and n.deleted = ?";
		$results = [];
		foreach($this->execute($sql, $params)->fetchAll() as $item){
			/**
			 * @var $note OwnNote
			 */
			$note = $this->makeEntityFromDBResult($item);
			$results[] = $note;
		}
		return $results;
	}
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return OwnNote[]
	 */
	public function findNotesByGroup($group, $userId) {
		$params = [$group];
		$uidSql = '';
		if($userId){
			$params[] = $userId;
			$uidSql = 'and n.uid = ?';
		}
		$sql = "SELECT n.uid, n.id, n.name, n.grouping, n.shared, n.mtime, n.deleted, p.pid, GROUP_CONCAT(p.note SEPARATOR '') as note FROM *PREFIX*ownnote n INNER JOIN *PREFIX*ownnote_parts p ON n.id = p.id WHERE n.deleted = 0 $uidSql and n.grouping = ? GROUP BY p.id";
		$results = [];
		foreach($this->execute($sql, $params)->fetchAll() as $item){
			$note = new OwnNote();
			$note->setId($item['id']);
			$note->setName($item['name']);
			$note->setGrouping($item['grouping']);
			$note->setMtime($item['mtime']);
			$note->setDeleted($item['deleted']);
			$note->setNote($item['note']);
			$note->setUid($item['uid']);
			$results[] = $note;
		}
		return $results;
	}

	/**
	 * Creates a note
	 *
	 * @param OwnNote $note
	 * @return OwnNote|Entity
	 * @internal param $userId
	 */
	public function create($note) {
		$parts = $this->utils->splitContent($note->getNote());
		$note->setNote('');
		/**
		 * @var $note OwnNote
		 */
		$note = parent::insert($note);

		foreach ($parts as $part) {
			$this->createNotePart($note, $part);
		}

		$note->setNote(implode('', $parts));

		return $note;
	}

	/**
	 * Update note
	 *
	 * @param OwnNote $note
	 * @return OwnNote|Entity
	 */
	public function updateNote($note) {
		$parts = $this->utils->splitContent($note->getNote());
		$this->deleteNoteParts($note);

		foreach ($parts as $part) {
			$this->createNotePart($note, $part);
		}
		$note->setNote('');
		/**
		 * @var $note OwnNote
		 */
		$note = parent::update($note);
		$note->setNote(implode('', $parts));
		return $note;
	}

	/**
	 * @param OwnNote $note
	 * @param $content
	 */
	public function createNotePart(OwnNote $note, $content) {
		$sql = "INSERT INTO *PREFIX*ownnote_parts VALUES (NULL, ?, ?);";
		$this->execute($sql, array($note->getId(), $content));
	}

	/**
	 * Delete the note parts
	 *
	 * @param OwnNote $note
	 */
	public function deleteNoteParts(OwnNote $note) {
		$sql = 'DELETE FROM *PREFIX*ownnote_parts where id = ?';
		$this->execute($sql, array($note->getId()));
	}

	/**
	 * Get the note parts
	 *
	 * @param OwnNote $note
	 * @return array
	 */
	public function getNoteParts(OwnNote $note) {
		$sql = 'SELECT * from *PREFIX*ownnote_parts where id = ?';
		return $this->execute($sql, array($note->getId()))->fetchAll();
	}

	/**
	 * @param OwnNote $note
	 * @return bool
	 */
	public function deleteNote(OwnNote $note) {
		$this->deleteNoteParts($note);
		$this->delete($note);
		return true;
	}

	/**
	 * @param $arr
	 * @return OwnNote
	 */
	public function makeEntityFromDBResult($arr){
		$note = new OwnNote();
		$note->setId($arr['id']);
		$note->setName($arr['name']);
		$note->setGrouping($arr['grouping']);
		$note->setMtime($arr['mtime']);
		$note->setDeleted($arr['deleted']);
		$note->setUid($arr['uid']);
		$noteParts = $this->getNoteParts($note);
		$partsTxt = implode('', array_map(function ($part) {
			return $part['note'];
		}, $noteParts));
		$note->setNote($partsTxt);
		return $note;
	}
}