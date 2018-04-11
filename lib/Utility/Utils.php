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

namespace OCA\NextNote\Utility;

class Utils {
    /**
     * Gets the unix epoch UTC timestamp
     * @return int
     */
    public static $maxPartSize = 2621440;

	public static function getTime() {
		return (new \DateTime())->getTimestamp();
	}
	/**
	 * @return int the current unix time in milliseconds
	 */
	public static function getMicroTime() {
		return microtime(true);
	}

	/**
	 * @param $uid
	 * @return string
	 */
	public static function getNameByUid($uid){
		$u = Utils::getUserInfo($uid);
		return $u['display_name'];
	}

	public static function getUserInfo($uid){
		$um = \OC::$server->getUserManager();
		$u = $um->get($uid);
		if($u) {
			$user = [
				'display_name' => $u->getDisplayName(),
				'uid' => $uid,
				'avatar' => $u->getAvatarImage(32)
			];
		} else {
			return false;
		}
		return $user;
	}

	/**
	 * Splits a string in parts of 5Mb
	 * @param $str
	 * @return array
	 */
	public function splitContent($str) {
		$maxlength = Utils::$maxPartSize; // 5 Megs (2 bytes per character)
		$count = 0;
		$strarray = array();
		while (true) {
			if (strlen($str) <= $maxlength) {
				$strarray[$count++] = $str;
				return $strarray;
			} else {
				$strarray[$count++] = substr($str, 0, $maxlength);
				$str = substr($str, $maxlength);
			}
		}
		return $strarray;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $array
	 * @return int|null|string
	 * @internal param $ $
	 */
	public static function getItemByProperty($key, $value, $array) {
		foreach ($array as $_key => $val) {
			if ($val[$key] === $value) {
				return $val;
			}
		}
		return null;
	}

	/**
	 * Generates a Globally Unique ID
	 * @return string
	 */
	public static function GUID() {
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
}