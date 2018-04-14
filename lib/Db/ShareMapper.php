<?php
/**
 * Nextcloud - NextNote
 *
 *
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

class ShareMapper extends Mapper {
	private $utils;

	public function __construct(IDBConnection $db, Utils $utils) {
		parent::__construct($db, 'nextnote_shares');
		$this->utils = $utils;
	}


	/**
	 * @param $share_id
	 * @param null $user_id
	 * @return Share|Share[]
	 */
	public function find($share_id, $user_id = null) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('nextnote_shares')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share_id)));

		if ($user_id) {
			$qb->andWhere($qb->expr()->eq('share_from', $qb->createNamedParameter($user_id)));
		}

		$results = [];
		$result = $qb->execute();
		while ($item = $result->fetch()) {
			/**
			 * @var $share Share
			 */
			$share = $this->makeEntityFromDBResult($item);
			$results[] = $share;
		}
		$result->closeCursor();
		if (count($results) === 1) {
			return reset($results);
		}
		return $results;

	}


	/**
	 * @param $userId
	 * @return Share[] if not found
	 */
	public function findSharesFromUser($userId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('nextnote_shares')
			->where($qb->expr()->eq('share_from', $qb->createNamedParameter($userId)));


		$results = [];
		$result = $qb->execute();
		while ($item = $result->fetch()) {
			/**
			 * @var $share Share
			 */
			$share = $this->makeEntityFromDBResult($item);

			$results[] = $share;
		}
		$result->closeCursor();
		return $results;
	}


	/**
	 * Creates a note
	 *
	 * @param Share|Entity $share
	 * @return Share|Entity
	 * @internal param $userId
	 */
	public function insert(Entity $share) {
		return parent::insert($share);
	}

	/**
	 * Update note
	 *
	 * @param Share|Entity $share
	 * @return Share|Entity
	 */
	public function updateNote(Entity $share) {
		parent::update($share);
		return $this->find($share->getId());
	}

	/**
	 * Update note
	 *
	 * @param Share|Entity $share
	 * @return Share|Entity
	 */
	public function delete(Entity $share) {
		parent::delete($share);
		return $this->find($share->getId());
	}

	public function makeEntityFromDBResult($arr) {
		$share = new Share();
		$share->setId($arr['id']);
		$share->setGuid($arr['guid']);
		$share->setShareFrom($arr['share_from']);
		$share->setShareTo($arr['share_to']);
		$share->setShareType($arr['share_type']);
		$share->setShareTarget($arr['share_target']);
		$share->setPermissions($arr['permissions']);
		$share->setExpireTime($arr['expire_time']);

		return $share;
	}
}
