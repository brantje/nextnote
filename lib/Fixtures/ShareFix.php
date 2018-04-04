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

namespace OCA\NextNote\Fixtures;

use OC\Share\Helper;
use OC\Share\Share;

class ShareFix extends Share{
	/**
	 * Set the permissions of an item for a specific user or group
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param int $permissions CRUDS permissions
	 * @return boolean true on success or false on failure
	 * @throws \Exception when trying to grant more permissions then the user has himself
	 */
	public static function setPermissions($itemType, $itemSource, $shareType, $shareWith, $permissions) {
		$l = \OC::$server->getL10N('lib');
		$connection = \OC::$server->getDatabaseConnection();

		$intArrayToLiteralArray = function($intArray, $eb) {
			return array_map(function($int) use ($eb) {
				return $eb->literal((int)$int, 'integer');
			}, $intArray);
		};
		$sanitizeItem = function($item) {
			$item['id'] = (int)$item['id'];
			$item['premissions'] = (int)$item['permissions'];
			return $item;
		};

		if ($rootItem = self::getItems($itemType, $itemSource, $shareType, $shareWith,
			\OC_User::getUser(), self::FORMAT_NONE, null, 1, false)) {
			// Check if this item is a reshare and verify that the permissions
			// granted don't exceed the parent shared item
			if (isset($rootItem['parent'])) {
				$qb = $connection->getQueryBuilder();
				$qb->select('permissions')
					->from('share')
					->where($qb->expr()->eq('id', $qb->createParameter('id')))
					->setParameter(':id', $rootItem['parent']);
				$dbresult = $qb->execute();

				$result = $dbresult->fetch();
				$dbresult->closeCursor();
				if (~(int)$result['permissions'] & $permissions) {
					$message = 'Setting permissions for %s failed,'
						.' because the permissions exceed permissions granted to %s';
					$message_t = $l->t('Setting permissions for %s failed, because the permissions exceed permissions granted to %s', array($itemSource, \OC_User::getUser()));
					\OCP\Util::writeLog('OCP\Share', sprintf($message, $itemSource, \OC_User::getUser()), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			}
			$qb = $connection->getQueryBuilder();
			$qb->update('share')
				->set('permissions', $qb->createParameter('permissions'))
				->where($qb->expr()->eq('id', $qb->createParameter('id')))
				->setParameter(':id', $rootItem['id'])
				->setParameter(':permissions', $permissions);
			$qb->execute();
			if ($itemType === 'file' || $itemType === 'folder') {
				\OC_Hook::emit('OCP\Share', 'post_update_permissions', array(
					'itemType' => $itemType,
					'itemSource' => $itemSource,
					'shareType' => $shareType,
					'shareWith' => $shareWith,
					'uidOwner' => \OC_User::getUser(),
					'permissions' => $permissions,
					'path' => $rootItem['path'],
					'share' => $rootItem
				));
			}

			// Share id's to update with the new permissions
			$ids = [];
			$items = [];

			// Check if permissions were removed
			if ((int)$rootItem['permissions'] & ~$permissions) {
				// If share permission is removed all reshares must be deleted
				if (($rootItem['permissions'] & \OCP\Constants::PERMISSION_SHARE) && (~$permissions & \OCP\Constants::PERMISSION_SHARE)) {
					// delete all shares, keep parent and group children
					Helper::delete($rootItem['id'], true, null, null, true);
				}

				// Remove permission from all children
				$parents = [$rootItem['id']];
				while (!empty($parents)) {
					$parents = $intArrayToLiteralArray($parents, $qb->expr());
					$qb = $connection->getQueryBuilder();
					$qb->select('id', 'permissions', 'item_type')
						->from('share')
						->where($qb->expr()->in('parent', $parents));
					$result = $qb->execute();
					// Reset parents array, only go through loop again if
					// items are found that need permissions removed
					$parents = [];
					while ($item = $result->fetch()) {
						$item = $sanitizeItem($item);

						$items[] = $item;
						// Check if permissions need to be removed
						if ($item['permissions'] & ~$permissions) {
							// Add to list of items that need permissions removed
							$ids[] = $item['id'];
							$parents[] = $item['id'];
						}
					}
					$result->closeCursor();
				}

				// Remove the permissions for all reshares of this item
				if (!empty($ids)) {
					$ids = "'".implode("','", $ids)."'";
					// TODO this should be done with Doctrine platform objects
					if (\OC::$server->getConfig()->getSystemValue("dbtype") === 'oci') {
						$andOp = 'BITAND(`permissions`, ?)';
					} else {
						$andOp = '`permissions` & ?';
					}
					$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `permissions` = '.$andOp
						.' WHERE `id` IN ('.$ids.')');
					$query->execute(array($permissions));
				}

			}

			/*
			 * Permissions were added
			 * Update all USERGROUP shares. (So group shares where the user moved their mountpoint).
			 */
			if ($permissions & ~(int)$rootItem['permissions']) {
				$qb = $connection->getQueryBuilder();
				$qb->select('id', 'permissions', 'item_type')
					->from('share')
					->where($qb->expr()->eq('parent', $qb->createParameter('parent')))
					->andWhere($qb->expr()->eq('share_type', $qb->createParameter('share_type')))
					->andWhere($qb->expr()->neq('permissions', $qb->createParameter('shareDeleted')))
					->setParameter(':parent', (int)$rootItem['id'])
					->setParameter(':share_type', 2)
					->setParameter(':shareDeleted', 0);
				$result = $qb->execute();

				$ids = [];
				while ($item = $result->fetch()) {
					$item = $sanitizeItem($item);
					$items[] = $item;
					$ids[] = $item['id'];
				}
				$result->closeCursor();

				// Add permssions for all USERGROUP shares of this item
				if (!empty($ids)) {
					$ids = $intArrayToLiteralArray($ids, $qb->expr());

					$qb = $connection->getQueryBuilder();
					$qb->update('share')
						->set('permissions', $qb->createParameter('permissions'))
						->where($qb->expr()->in('id', $ids))
						->setParameter(':permissions', $permissions);
					$qb->execute();
				}
			}

			foreach ($items as $item) {
				\OC_Hook::emit('OCP\Share', 'post_update_permissions', ['share' => $item]);
			}

			return true;
		}
		$message = 'Setting permissions for %s failed, because the item was not found';
		$message_t = $l->t('Setting permissions for %s failed, because the item was not found', array($itemSource));

		\OCP\Util::writeLog('OCP\Share', sprintf($message, $itemSource), \OCP\Util::DEBUG);
		throw new \Exception($message_t);
	}

	/**
	 * Get the item of item type shared with the current user
	 * @param string $itemType
	 * @param string $itemTarget
	 * @param int $format (optional) Format type must be defined by the backend
	 * @param mixed $parameters (optional)
	 * @param boolean $includeCollections (optional)
	 * @return mixed Return depends on format
	 */
	public static function getItemSharedWith($itemType, $itemTarget, $format = self::FORMAT_NONE,
											 $parameters = null, $includeCollections = false) {
		return parent::getItems($itemType, $itemTarget, self::$shareTypeUserAndGroups, \OC_User::getUser(), null, $format,
			$parameters, 1, $includeCollections);
	}


	/**
	 * Get all users an item is shared with
	 * @param string $itemType
	 * @param string $itemSource
	 * @param string $uidOwner
	 * @param boolean $includeCollections
	 * @param boolean $checkExpireDate
	 * @return array Return array of users
	 */
	public static function getUsersItemShared($itemType, $itemSource, $uidOwner, $includeCollections = false, $checkExpireDate = true) {

		$users = array();
		$items = self::getItems($itemType, $itemSource, null, null, $uidOwner, self::FORMAT_NONE, null, -1, $includeCollections, false, $checkExpireDate);
		if ($items) {
			foreach ($items as $item) {
				if ((int)$item['share_type'] === self::SHARE_TYPE_USER) {
					$users[] = $item['share_with'];
				} else if ((int)$item['share_type'] === self::SHARE_TYPE_GROUP) {

					$group = \OC::$server->getGroupManager()->get($item['share_with']);
					$userIds = [];
					if ($group) {
						$users = $group->searchUsers('', -1, 0);
						foreach ($users as $user) {
							$userIds[] = $user->getUID();
						}
						return $userIds;
					}

					$users = array_merge($users, $userIds);
				}
			}
		}
		return $users;
	}

}