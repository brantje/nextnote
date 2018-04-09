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

use \OCP\AppFramework\Db\Entity;

/**
 * @method integer getId()
 * @method void setId(int $value)
 * @method integer getParentId()
 * @method void setParentId(int $value)
 * @method void setUid(string $value)
 * @method string getUid()
 * @method void setName(string $value)
 * @method string getName()
 * @method void setGuid(string $value)
 * @method string getGuid()
 * @method string getColor()
 * @method string setColor(string $value)
 * @method void setDeleted(integer $value)
 * @method integer getDeleted()
 * @method void setNoteCount(integer $value)
 * @method integer getNoteCount()
 */
class Notebook extends Entity implements \JsonSerializable {

	use EntityJSONSerializer;

	protected $name;
	protected $guid;
	protected $uid;
	protected $parentId;
	protected $color;
	protected $deleted;
	protected $noteCount;

	public function __construct() {
		// add types in constructor
		$this->addType('parentId', 'integer');
		$this->addType('deleted', 'integer');
		$this->addType('note_count', 'integer');
	}

	/**
	 * Turns entity attributes into an array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'guid' => $this->getGuid(),
			'parent_id' => $this->getParentId(),
			'name' => $this->getName(),
			'color' => $this->getColor(),
			'note_count' => $this->getNoteCount(),
			'permissions' => 31
		];
	}
}
