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

namespace OCA\NextNote\Migration;


use OCA\NextNote\Db\Notebook;
use OCA\NextNote\Db\Note;
use OCA\NextNote\Service\NotebookService;
use OCA\NextNote\Service\NoteService;
use OCA\NextNote\Utility\Utils;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;


class MigrateGroups implements IRepairStep {


	/** @var IDBConnection */
	private $db;

	/** @var string */
	private $installedVersion;

	/** @var ILogger */
	private $logger;
	private $groupService;
	private $noteService;


	public function __construct(IDBConnection $db, ILogger $logger, NotebookService $groupService, NoteService $noteService) {
		$this->db = $db;
		$this->logger = $logger;
		$this->installedVersion = \OC::$server->getConfig()->getAppValue('nextnote', 'installed_version');
		$this->groupService = $groupService;
		$this->noteService = $noteService;
	}

	public function getName() {
		return 'Migrating groups';
	}

	public function run(IOutput $output) {
		$output->info('Migrating groups');
		$this->doMigration();
	}

	private function fetchAll($sql) {
		return $this->db->executeQuery($sql)->fetchAll();
	}

	private function doMigration() {
		if (version_compare($this->installedVersion, '1.2.3', '<')) {
			$users = $this->fetchAll('SELECT DISTINCT(uid) FROM `*PREFIX*nextnote`');
			foreach ($users as $user) {
				$user = $user['uid'];
				$groups = $this->fetchAll('SELECT DISTINCT(grouping) FROM `*PREFIX*nextnote` WHERE uid="' . $user . '"');
				foreach ($groups as $group) {
					if ($group['grouping']) {
						$g = new Notebook();
						$g->setName($group['grouping']);
						$g->setUid($user);
						$g->setGuid(Utils::GUID());
						$this->groupService->create($g, $user);
						//$this->db->executeQuery("UPDATE `*PREFIX*nextnote` set grouping =". $g->getId() ." WHERE grouping = \"". $group['grouping'] ."\"");
					}
				}
			}
			$notes = $this->fetchAll('SELECT * FROM *PREFIX*nextnote order by id ASC');
			$maxId = 0;
			foreach($notes as $n){
				if($n['id'] > $maxId){
					$maxId = $n['id'];
				}
				$notebook = $this->groupService->findByName($n['grouping']);
				$note = new Note();
				$note->setId($n['id']);
				$note->setGuid(Utils::GUID());
				$note->setUid($n['uid']);
				$note->setName($n['name']);
				$note->setMtime($n['mtime']);
				$note->setDeleted($n['deleted']);
				$note->setGrouping($notebook->getId());
				$this->noteService->create($note);
			}
			$maxId++;
			$this->db->executeQuery('ALTER TABLE *PREFIX*nextnote_notes AUTO_INCREMENT='. $maxId);
			$this->db->executeQuery('DROP TABLE *PREFIX*nextnote');
		}
	}

}
