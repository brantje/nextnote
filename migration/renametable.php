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


use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;


class RenameTable implements IRepairStep {


	/** @var IDBConnection */
	private $db;

	/** @var string */
	private $installedVersion;

	/** @var ILogger */
	private $logger;


	public function __construct(IDBConnection $db, ILogger $logger) {
		$this->db = $db;
		$this->logger = $logger;
	}

	public function getName() {
		return 'Copying notes from ownnotes';
	}

	public function run(IOutput $output) {
		$output->info('Copying notes from ownnotes');
		$this->copyOwnNotes();
	}

	private function copyOwnNotes() {
		try {
			$this->db->executeQuery('INSERT INTO `*PREFIX*nextnote` SELECT * FROM *PREFIX*ownnote');
			$this->db->executeQuery('DROP TABLE *PREFIX*ownnote');
		} catch (\Exception $exception) {
			$this->logger->error($exception->getMessage());
		}
		try {
			$this->db->executeQuery('INSERT INTO `*PREFIX*nextnote_parts` SELECT * FROM *PREFIX*ownnote_parts');
			$this->db->executeQuery('DROP TABLE *PREFIX*ownnote_parts');
		} catch (\Exception $exception) {
			$this->logger->error($exception->getMessage());
		}
	}

}
