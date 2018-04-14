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
use OCA\NextNote\Db\Note;
use OCA\NextNote\Db\ShareMapper;
use OCA\NextNote\Fixtures\ExampleNote;
use OCA\NextNote\Utility\Utils;
use OCA\NextNote\Db\NoteMapper;


class ShareService {

	const PERMISSION_CREATE = 4;
	const PERMISSION_READ = 1;
	const PERMISSION_UPDATE = 2;
	const PERMISSION_DELETE = 8;
	const PERMISSION_SHARE = 16;
	const PERMISSION_ALL = 31;


	private $shareMapper;
	private $utils;
	private $notebookService;

	public function __construct(ShareMapper $shareMapper, Utils $utils, NotebookService $notebookService) {
		$this->shareMapper = $shareMapper;
		$this->utils = $utils;
		$this->notebookService = $notebookService;
	}

	/**
	 * Get shared notes from a user.
	 *
	 * @param $userId
	 * @param int|bool $deleted
	 * @return Note[]
	 */
	public function getSharedNotesFromUser($userId, $deleted = false) {
		// Get shares

	}
}
