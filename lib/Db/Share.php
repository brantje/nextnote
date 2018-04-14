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
use \OCP\AppFramework\Db\Entity;

/**
 * @method integer getId()
 * @method void setId(int $value)
 * @method void setGuid(string $value)
 * @method string getGuid()
 * @method void setShareFrom(string $value)
 * @method string getShareFrom()
 * @method void setShareTo(string $value)
 * @method string getShareTo()
 * @method void setShareType(string $value)
 * @method string getShareType()
 * @method void setShareTarget(string $value)
 * @method string getShareTarget()
 * @method void setPermissions(string $value)
 * @method string getPermissions()
 * @method void setExpireTime(string $value)
 * @method string getExpireTime()

 */


class Share extends Entity implements  \JsonSerializable{

	use EntityJSONSerializer;

	protected $guid;
	protected $shareFrom; //User from
	protected $shareTo; // Share to User / group
	protected $shareType; // Note (1) or Notebook(2)
	protected $shareTarget; //id of the entity
	protected $permissions; // int permissions
	protected $expireTime; // Expire time of share. 0 to disable


	public function __construct() {
		// add types in constructor
		$this->addType('permissions', 'integer');
		$this->addType('shareTarget', 'integer');
		$this->addType('shareType', 'integer');
	}
	/**
	 * Turns entity attributes into an array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'guid' => $this->getGuid(),
			'share_from' => $this->getShareFrom(),
			'share_to' => $this->getShareTo(),
			'share_type' => $this->getShareType(),
			'share_target' => $this->getShareTarget(),
			'permissions' => $this->getPermissions(),
			'expire_time' => $this->getExpireTime(),
		];
	}
}
