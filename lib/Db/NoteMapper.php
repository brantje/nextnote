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

use OCA\NextNote\Service\NotebookService;
use \OCA\NextNote\Utility\Utils;
use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class NoteMapper extends Mapper {
	private $utils;
	private $notebookService;

	public function __construct(IDBConnection $db, Utils $utils, NotebookService $notebookService) {
		parent::__construct($db, 'nextnote_notes');
		$this->utils = $utils;
		$this->notebookService = $notebookService;
	}


	/**
	 * @param $note_id
	 * @param null $user_id
	 * @param int|bool $deleted
	 * @return Note if not found
	 */
	public function find($note_id, $user_id = null, $deleted = false) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('nextnote_notes')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($note_id)));

		if ($user_id) {
			$qb->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($user_id)));
		}

		if ($deleted !== false) {
			$qb->andWhere($qb->expr()->eq('deleted', $qb->createNamedParameter($deleted)));
		}

		$results = [];
		$result = $qb->execute();
		while ($item = $result->fetch()) {
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
		$result->closeCursor();
		if (count($results) === 1) {
			return reset($results);
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
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('nextnote_notes')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)));

		if ($group) {
			$qb->andWhere($qb->expr()->eq('notebook', $qb->createNamedParameter($group)));
		}

		if ($deleted !== false) {
			$qb->andWhere($qb->expr()->eq('notebook', $qb->createNamedParameter((int)$deleted)));
		}

		$results = [];
		$result = $qb->execute();
		while ($item = $result->fetch()) {
			/**
			 * @var $note Note
			 */
			$note = $this->makeEntityFromDBResult($item);
			$note->setNote($item['note']);

			$results[] = $note;
		}
		$result->closeCursor();
		if (count($results) === 1) {
			return reset($results);
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
	public function insert($note) {
		$len = mb_strlen($note->getNote());
		$parts = false;
		if ($len > Utils::$maxPartSize) {
			$parts = $this->utils->splitContent($note->getNote());
			$note->setNote('');
		}
		$note->setShared(false);

		$note = parent::insert($note);
		/**
		 * @var $note Note
		 */
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
		return $this->find($note->getId());
	}

	/**
	 * @param Note $note
	 * @param $content
	 */
	public function createNotePart(Note $note, $content) {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('nextnote_parts')
			->values([
				'id' => $qb->createNamedParameter($note->getId()),
				'note' => $qb->createNamedParameter($content),
			]);
		$qb->execute();
	}

	/**
	 * Delete the note parts
	 *
	 * @param Note $note
	 */
	public function deleteNoteParts(Note $note) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('nextnote_parts')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($note->getId())));
		$qb->execute();
	}

	/**
	 * Get the note parts
	 *
	 * @param Note $note
	 * @return array
	 */
	public function getNoteParts(Note $note) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('nextnote_parts')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($note->getId())));
		$result = $qb->execute();
		$results = $result->fetchAll();
		$result->closeCursor();

		return $results;
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
		if ($arr['notebook']) {
			$notebook = $this->notebookService->find($arr['notebook']);
			$note->setNotebook($notebook);
		}
		$note->setMtime($arr['mtime']);
		$note->setDeleted($arr['deleted']);
		$note->setUid($arr['uid']);
		return $note;
	}
}
