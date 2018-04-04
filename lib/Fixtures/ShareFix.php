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
use OCA\NextNote\Utility\Utils;

class ShareFix extends Share{

	private static function log($level, $message, $context){
		\OC::$server->getLogger()->log($level, $message, $context);
	}

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
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSource, \OC_User::getUser()), \OCP\Util::DEBUG);
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
				\OC_Hook::emit('NextNote\Fixtures\ShareFix', 'post_update_permissions', array(
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

		self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSource), \OCP\Util::DEBUG);
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
	public static function getItemSharedWith($itemType, $itemTarget, $format = parent::FORMAT_NONE,
											 $parameters = null, $includeCollections = false) {

		return self::getItems($itemType, $itemTarget, parent::$shareTypeUserAndGroups, \OC_User::getUser(), null, self::FORMAT_NONE,
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
		$queryArgs = [
			$itemType,
			$itemSource
		];
		$where = '`item_type` = ?';
		$where .= ' AND `item_source`= ?';
		$q = 'SELECT * FROM `*PREFIX*share` WHERE '.$where;
		$query = \OC_DB::prepare($q);

		$result = $query->execute($queryArgs);
		while ($row = $result->fetchRow()) {
			if($row['share_type'] == self::SHARE_TYPE_USER){
				$u = Utils::getUserInfo($row['share_with']);
				$users[] = $u['display_name'];
			}
			if($row['share_type'] == self::SHARE_TYPE_GROUP){
				$users[] = $row['share_with'];
			}
		}

		return $users;
	}


	/**
	 * Share an item with a user, group, or via private link
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param int $permissions CRUDS
	 * @param string $itemSourceName
	 * @param \DateTime|null $expirationDate
	 * @param bool|null $passwordChanged
	 * @return boolean|string Returns true on success or false on failure, Returns token on success for links
	 * @throws \OC\HintException when the share type is remote and the shareWith is invalid
	 * @throws \Exception
	 * @since 5.0.0 - parameter $itemSourceName was added in 6.0.0, parameter $expirationDate was added in 7.0.0, parameter $passwordChanged added in 9.0.0
	 */
	public static function shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions, $itemSourceName = null, \DateTime $expirationDate = null, $passwordChanged = null) {

		$backend = self::getBackend($itemType);
		$l = \OC::$server->getL10N('lib');

		if ($backend->isShareTypeAllowed($shareType) === false) {
			$message = 'Sharing %s failed, because the backend does not allow shares from type %i';
			$message_t = $l->t('Sharing %s failed, because the backend does not allow shares from type %i', array($itemSourceName, $shareType));
			self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareType), \OCP\Util::DEBUG);
			throw new \Exception($message_t);
		}

		$uidOwner = \OC_User::getUser();
		$shareWithinGroupOnly = self::shareWithGroupMembersOnly();

		if (is_null($itemSourceName)) {
			$itemSourceName = $itemSource;
		}
		$itemName = $itemSourceName;

		//Validate expirationDate
		if ($expirationDate !== null) {
			try {
				/*
				 * Reuse the validateExpireDate.
				 * We have to pass time() since the second arg is the time
				 * the file was shared, since it is not shared yet we just use
				 * the current time.
				 */
				$expirationDate = self::validateExpireDate($expirationDate->format('Y-m-d'), time(), $itemType, $itemSource);
			} catch (\Exception $e) {
				throw new \OC\HintException($e->getMessage(), $e->getMessage(), 404);
			}
		}

		// Verify share type and sharing conditions are met
		if ($shareType === self::SHARE_TYPE_USER) {
			if ($shareWith == $uidOwner) {
				$message = 'Sharing %s failed, because you can not share with yourself';
				$message_t = $l->t('Sharing %s failed, because you can not share with yourself', [$itemName]);
				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
			if (!\OC::$server->getUserManager()->userExists($shareWith)) {
				$message = 'Sharing %s failed, because the user %s does not exist';
				$message_t = $l->t('Sharing %s failed, because the user %s does not exist', array($itemSourceName, $shareWith));
				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
			if ($shareWithinGroupOnly) {
				$userManager = \OC::$server->getUserManager();
				$groupManager = \OC::$server->getGroupManager();
				$userOwner = $userManager->get($uidOwner);
				$userShareWith = $userManager->get($shareWith);
				$groupsOwner = [];
				$groupsShareWith = [];
				if ($userOwner) {
					$groupsOwner = $groupManager->getUserGroupIds($userOwner);
				}
				if ($userShareWith) {
					$groupsShareWith = $groupManager->getUserGroupIds($userShareWith);
				}
				$inGroup = array_intersect($groupsOwner, $groupsShareWith);
				if (empty($inGroup)) {
					$message = 'Sharing %s failed, because the user '
						.'%s is not a member of any groups that %s is a member of';
					$message_t = $l->t('Sharing %s failed, because the user %s is not a member of any groups that %s is a member of', array($itemName, $shareWith, $uidOwner));
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemName, $shareWith, $uidOwner), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			}
			// Check if the item source is already shared with the user, either from the same owner or a different user
			if ($checkExists = self::getItems($itemType, $itemSource, self::$shareTypeUserAndGroups,
				$shareWith, null, self::FORMAT_NONE, null, 1, true, true)) {
				// Only allow the same share to occur again if it is the same
				// owner and is not a user share, this use case is for increasing
				// permissions for a specific user
				if ($checkExists['uid_owner'] != $uidOwner || $checkExists['share_type'] == $shareType) {
					$message = 'Sharing %s failed, because this item is already shared with %s';
					$message_t = $l->t('Sharing %s failed, because this item is already shared with %s', array($itemSourceName, $shareWith));
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			}
			if ($checkExists = self::getItems($itemType, $itemSource, self::SHARE_TYPE_USER,
				$shareWith, null, self::FORMAT_NONE, null, 1, true, true)) {
				// Only allow the same share to occur again if it is the same
				// owner and is not a user share, this use case is for increasing
				// permissions for a specific user
				if ($checkExists['uid_owner'] != $uidOwner || $checkExists['share_type'] == $shareType) {
					$message = 'Sharing %s failed, because this item is already shared with user %s';
					$message_t = $l->t('Sharing %s failed, because this item is already shared with user %s', array($itemSourceName, $shareWith));
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::ERROR);
					throw new \Exception($message_t);
				}
			}
		} else if ($shareType === self::SHARE_TYPE_GROUP) {
			if (!\OC::$server->getGroupManager()->groupExists($shareWith)) {
				$message = 'Sharing %s failed, because the group %s does not exist';
				$message_t = $l->t('Sharing %s failed, because the group %s does not exist', array($itemSourceName, $shareWith));
				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
			if ($shareWithinGroupOnly) {
				$group = \OC::$server->getGroupManager()->get($shareWith);
				$user = \OC::$server->getUserManager()->get($uidOwner);
				if (!$group || !$user || !$group->inGroup($user)) {
					$message = 'Sharing %s failed, because '
						. '%s is not a member of the group %s';
					$message_t = $l->t('Sharing %s failed, because %s is not a member of the group %s', array($itemSourceName, $uidOwner, $shareWith));
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $uidOwner, $shareWith), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			}
			// Check if the item source is already shared with the group, either from the same owner or a different user
			// The check for each user in the group is done inside the put() function
			if ($checkExists = self::getItems($itemType, $itemSource, self::SHARE_TYPE_GROUP, $shareWith,
				null, self::FORMAT_NONE, null, 1, true, true)) {

				if ($checkExists['share_with'] === $shareWith && $checkExists['share_type'] === \OCP\Share::SHARE_TYPE_GROUP) {
					$message = 'Sharing %s failed, because this item is already shared with %s';
					$message_t = $l->t('Sharing %s failed, because this item is already shared with %s', array($itemSourceName, $shareWith));
					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			}
			// Convert share with into an array with the keys group and users
			$group = $shareWith;
			$shareWith = array();
			$shareWith['group'] = $group;


			$groupObject = \OC::$server->getGroupManager()->get($group);
			$userIds = [];
			if ($groupObject) {
				$users = $groupObject->searchUsers('');
				foreach ($users as $user) {
					$userIds[] = $user->getUID();
				}
			}


			$shareWith['users'] = array_diff($userIds, array($uidOwner));
		} else {
			// Future share types need to include their own conditions
			$message = 'Share type %s is not valid for %s';
			$message_t = $l->t('Share type %s is not valid for %s', array($shareType, $itemSource));
			self::log('NextNote\Fixtures\ShareFix', sprintf($message, $shareType, $itemSource), \OCP\Util::DEBUG);
			throw new \Exception($message_t);
		}

		// Put the item into the database
		$result = self::put($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions, null, null, $itemSourceName, $expirationDate);

		return $result ? true : false;
	}

	/**
	 * Put shared item into the database
	 * @param string $itemType Item type
	 * @param string $itemSource Item source
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param string $uidOwner User that is the owner of shared item
	 * @param int $permissions CRUDS permissions
	 * @param boolean|array $parentFolder Parent folder target (optional)
	 * @param string $token (optional)
	 * @param string $itemSourceName name of the source item (optional)
	 * @param \DateTime $expirationDate (optional)
	 * @throws \Exception
	 * @return mixed id of the new share or false
	 */
	private static function put($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
								$permissions, $parentFolder = null, $token = null, $itemSourceName = null, \DateTime $expirationDate = null) {

		$queriesToExecute = array();
		$suggestedItemTarget = null;
		$groupFileTarget = $fileTarget = $suggestedFileTarget = $filePath = '';
		$groupItemTarget = $itemTarget = $fileSource = $parent = 0;

		$result = self::checkReshare($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions, $itemSourceName, $expirationDate);
		if(!empty($result)) {
			$parent = $result['parent'];
			$itemSource = $result['itemSource'];
			$fileSource = $result['fileSource'];
			$suggestedItemTarget = $result['suggestedItemTarget'];
			$suggestedFileTarget = $result['suggestedFileTarget'];
			$filePath = $result['filePath'];
		}

		$isGroupShare = false;
		if ($shareType == self::SHARE_TYPE_GROUP) {
			$isGroupShare = true;
			if (isset($shareWith['users'])) {
				$users = $shareWith['users'];
			} else {
				$group = \OC::$server->getGroupManager()->get($shareWith['group']);
				if ($group) {
					$users = $group->searchUsers('');
					$userIds = [];
					foreach ($users as $user) {
						$userIds[] = $user->getUID();
					}
					$users = $userIds;
				} else {
					$users = [];
				}
			}
			// remove current user from list
			if (in_array(\OC::$server->getUserSession()->getUser()->getUID(), $users)) {
				unset($users[array_search(\OC::$server->getUserSession()->getUser()->getUID(), $users)]);
			}
			$groupItemTarget = Helper::generateTarget($itemType, $itemSource,
				$shareType, $shareWith['group'], $uidOwner, $suggestedItemTarget);
			$groupFileTarget = Helper::generateTarget($itemType, $itemSource,
				$shareType, $shareWith['group'], $uidOwner, $filePath);

			// add group share to table and remember the id as parent
			$queriesToExecute['groupShare'] = array(
				'itemType'			=> $itemType,
				'itemSource'		=> $itemSource,
				'itemTarget'		=> $groupItemTarget,
				'shareType'			=> $shareType,
				'shareWith'			=> $shareWith['group'],
				'uidOwner'			=> $uidOwner,
				'permissions'		=> $permissions,
				'shareTime'			=> time(),
				'fileSource'		=> $fileSource,
				'fileTarget'		=> $groupFileTarget,
				'token'				=> $token,
				'parent'			=> $parent,
				'expiration'		=> $expirationDate,
			);

		} else {
			$users = array($shareWith);
			$itemTarget = Helper::generateTarget($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
				$suggestedItemTarget);
		}

		$run = true;
		$error = '';
		$preHookData = array(
			'itemType' => $itemType,
			'itemSource' => $itemSource,
			'shareType' => $shareType,
			'uidOwner' => $uidOwner,
			'permissions' => $permissions,
			'fileSource' => $fileSource,
			'expiration' => $expirationDate,
			'token' => $token,
			'run' => &$run,
			'error' => &$error
		);

		$preHookData['itemTarget'] = $isGroupShare ? $groupItemTarget : $itemTarget;
		$preHookData['shareWith'] = $isGroupShare ? $shareWith['group'] : $shareWith;

		\OC_Hook::emit(\OCP\Share::class, 'pre_shared', $preHookData);

		if ($run === false) {
			throw new \Exception($error);
		}

		foreach ($users as $user) {
			$sourceId = ($itemType === 'file' || $itemType === 'folder') ? $fileSource : $itemSource;
			$sourceExists = self::getItemSharedWithBySource($itemType, $sourceId, self::FORMAT_NONE, null, true, $user);

			$userShareType = $isGroupShare ? self::$shareTypeGroupUserUnique : $shareType;

			if ($sourceExists && $sourceExists['item_source'] === $itemSource) {
				$fileTarget = $sourceExists['file_target'];
				$itemTarget = $sourceExists['item_target'];

				// for group shares we don't need a additional entry if the target is the same
				if($isGroupShare && $groupItemTarget === $itemTarget) {
					continue;
				}

			} elseif(!$sourceExists && !$isGroupShare)  {

				$itemTarget = Helper::generateTarget($itemType, $itemSource, $userShareType, $user,
					$uidOwner, $suggestedItemTarget, $parent);
				if (isset($fileSource)) {
					if ($parentFolder) {
						if ($parentFolder === true) {
							$fileTarget = Helper::generateTarget('file', $filePath, $userShareType, $user,
								$uidOwner, $suggestedFileTarget, $parent);
							if ($fileTarget != $groupFileTarget) {
								$parentFolders[$user]['folder'] = $fileTarget;
							}
						} else if (isset($parentFolder[$user])) {
							$fileTarget = $parentFolder[$user]['folder'].$itemSource;
							$parent = $parentFolder[$user]['id'];
						}
					} else {
						$fileTarget = Helper::generateTarget('file', $filePath, $userShareType,
							$user, $uidOwner, $suggestedFileTarget, $parent);
					}
				} else {
					$fileTarget = null;
				}

			} else {

				// group share which doesn't exists until now, check if we need a unique target for this user

				$itemTarget = Helper::generateTarget($itemType, $itemSource, self::SHARE_TYPE_USER, $user,
					$uidOwner, $suggestedItemTarget, $parent);

				// do we also need a file target
				if (isset($fileSource)) {
					$fileTarget = Helper::generateTarget('file', $filePath, self::SHARE_TYPE_USER, $user,
						$uidOwner, $suggestedFileTarget, $parent);
				} else {
					$fileTarget = null;
				}

				if (($itemTarget === $groupItemTarget) &&
					(!isset($fileSource) || $fileTarget === $groupFileTarget)) {
					continue;
				}
			}

			$queriesToExecute[] = array(
				'itemType'			=> $itemType,
				'itemSource'		=> $itemSource,
				'itemTarget'		=> $itemTarget,
				'shareType'			=> $userShareType,
				'shareWith'			=> $user,
				'uidOwner'			=> $uidOwner,
				'permissions'		=> $permissions,
				'shareTime'			=> time(),
				'fileSource'		=> $fileSource,
				'fileTarget'		=> $fileTarget,
				'token'				=> $token,
				'parent'			=> $parent,
				'expiration'		=> $expirationDate,
			);

		}

		$id = false;
		if ($isGroupShare) {
			$id = self::insertShare($queriesToExecute['groupShare']);
			// Save this id, any extra rows for this group share will need to reference it
			$parent = \OC::$server->getDatabaseConnection()->lastInsertId('*PREFIX*share');
			unset($queriesToExecute['groupShare']);
		}

		foreach ($queriesToExecute as $shareQuery) {
			$shareQuery['parent'] = $parent;
			$id = self::insertShare($shareQuery);
		}

		$postHookData = array(
			'itemType' => $itemType,
			'itemSource' => $itemSource,
			'parent' => $parent,
			'shareType' => $shareType,
			'uidOwner' => $uidOwner,
			'permissions' => $permissions,
			'fileSource' => $fileSource,
			'id' => $parent,
			'token' => $token,
			'expirationDate' => $expirationDate,
		);

		$postHookData['shareWith'] = $isGroupShare ? $shareWith['group'] : $shareWith;
		$postHookData['itemTarget'] = $isGroupShare ? $groupItemTarget : $itemTarget;
		$postHookData['fileTarget'] = $isGroupShare ? $groupFileTarget : $fileTarget;

		\OC_Hook::emit(\OCP\Share::class, 'post_shared', $postHookData);


		return $id ? $id : false;
	}

	/**
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType
	 * @param string $shareWith
	 * @param string $uidOwner
	 * @param int $permissions
	 * @param string|null $itemSourceName
	 * @param null|\DateTime $expirationDate
	 */
	private static function checkReshare($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $permissions, $itemSourceName, $expirationDate) {
		$backend = self::getBackend($itemType);

		$l = \OC::$server->getL10N('lib');
		$result = array();

		$column = ($itemType === 'file' || $itemType === 'folder') ? 'file_source' : 'item_source';

		$checkReshare = self::getItemSharedWithBySource($itemType, $itemSource, self::FORMAT_NONE, null, true);
		if ($checkReshare) {
			// Check if attempting to share back to owner
			if ($checkReshare['uid_owner'] == $shareWith && $shareType == self::SHARE_TYPE_USER) {
				$message = 'Sharing %s failed, because the user %s is the original sharer';
				$message_t = $l->t('Sharing failed, because the user %s is the original sharer', [$shareWith]);

				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $shareWith), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
		}

		if ($checkReshare && $checkReshare['uid_owner'] !== \OC_User::getUser()) {
			// Check if share permissions is granted
			if (self::isResharingAllowed() && (int)$checkReshare['permissions'] & \OCP\Constants::PERMISSION_SHARE) {
				if (~(int)$checkReshare['permissions'] & $permissions) {
					$message = 'Sharing %s failed, because the permissions exceed permissions granted to %s';
					$message_t = $l->t('Sharing %s failed, because the permissions exceed permissions granted to %s', array($itemSourceName, $uidOwner));

					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName, $uidOwner), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				} else {
					// TODO Don't check if inside folder
					$result['parent'] = $checkReshare['id'];

					$result['expirationDate'] = $expirationDate;
					// $checkReshare['expiration'] could be null and then is always less than any value
					if(isset($checkReshare['expiration']) && $checkReshare['expiration'] < $expirationDate) {
						$result['expirationDate'] = $checkReshare['expiration'];
					}

					// only suggest the same name as new target if it is a reshare of the
					// same file/folder and not the reshare of a child
					if ($checkReshare[$column] === $itemSource) {
						$result['filePath'] = $checkReshare['file_target'];
						$result['itemSource'] = $checkReshare['item_source'];
						$result['fileSource'] = $checkReshare['file_source'];
						$result['suggestedItemTarget'] = $checkReshare['item_target'];
						$result['suggestedFileTarget'] = $checkReshare['file_target'];
					} else {
						$result['filePath'] = ($backend instanceof \OCP\Share_Backend_File_Dependent) ? $backend->getFilePath($itemSource, $uidOwner) : null;
						$result['suggestedItemTarget'] = null;
						$result['suggestedFileTarget'] = null;
						$result['itemSource'] = $itemSource;
						$result['fileSource'] = ($backend instanceof \OCP\Share_Backend_File_Dependent) ? $itemSource : null;
					}
				}
			} else {
				$message = 'Sharing %s failed, because resharing is not allowed';
				$message_t = $l->t('Sharing %s failed, because resharing is not allowed', array($itemSourceName));

				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSourceName), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
		} else {
			$result['parent'] = null;
			$result['suggestedItemTarget'] = null;
			$result['suggestedFileTarget'] = null;
			$result['itemSource'] = $itemSource;
			$result['expirationDate'] = $expirationDate;
			if (!$backend->isValidSource($itemSource, $uidOwner)) {
				$message = 'Sharing %s failed, because the sharing backend for '
					.'%s could not find its source';
				$message_t = $l->t('Sharing %s failed, because the sharing backend for %s could not find its source', array($itemSource, $itemType));
				self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSource, $itemType), \OCP\Util::DEBUG);
				throw new \Exception($message_t);
			}
			if ($backend instanceof \OCP\Share_Backend_File_Dependent) {
				$result['filePath'] = $backend->getFilePath($itemSource, $uidOwner);
				if ($itemType == 'file' || $itemType == 'folder') {
					$result['fileSource'] = $itemSource;
				} else {
					$meta = \OC\Files\Filesystem::getFileInfo($result['filePath']);
					$result['fileSource'] = $meta['fileid'];
				}
				if ($result['fileSource'] == -1) {
					$message = 'Sharing %s failed, because the file could not be found in the file cache';
					$message_t = $l->t('Sharing %s failed, because the file could not be found in the file cache', array($itemSource));

					self::log('NextNote\Fixtures\ShareFix', sprintf($message, $itemSource), \OCP\Util::DEBUG);
					throw new \Exception($message_t);
				}
			} else {
				$result['filePath'] = null;
				$result['fileSource'] = null;
			}
		}

		return $result;
	}

	/**
	 *
	 * @param array $shareData
	 * @return mixed false in case of a failure or the id of the new share
	 */
	private static function insertShare(array $shareData) {

		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`, `parent`, `expiration`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$query->bindValue(1, $shareData['itemType']);
		$query->bindValue(2, $shareData['itemSource']);
		$query->bindValue(3, $shareData['itemTarget']);
		$query->bindValue(4, $shareData['shareType']);
		$query->bindValue(5, $shareData['shareWith']);
		$query->bindValue(6, $shareData['uidOwner']);
		$query->bindValue(7, $shareData['permissions']);
		$query->bindValue(8, $shareData['shareTime']);
		$query->bindValue(9, $shareData['fileSource']);
		$query->bindValue(10, $shareData['fileTarget']);
		$query->bindValue(11, $shareData['token']);
		$query->bindValue(12, $shareData['parent']);
		$query->bindValue(13, $shareData['expiration'], 'datetime');
		$result = $query->execute();

		$id = false;
		if ($result) {
			$id =  \OC::$server->getDatabaseConnection()->lastInsertId('*PREFIX*share');
		}

		return $id;

	}

	/**
	 * validate expiration date if it meets all constraints
	 *
	 * @param string $expireDate well formatted date string, e.g. "DD-MM-YYYY"
	 * @param string $shareTime timestamp when the file was shared
	 * @param string $itemType
	 * @param string $itemSource
	 * @return \DateTime validated date
	 * @throws \Exception when the expire date is in the past or further in the future then the enforced date
	 */
	private static function validateExpireDate($expireDate, $shareTime, $itemType, $itemSource) {
		$l = \OC::$server->getL10N('lib');
		$date = new \DateTime($expireDate);
		$today = new \DateTime('now');

		// if the user doesn't provide a share time we need to get it from the database
		// fall-back mode to keep API stable, because the $shareTime parameter was added later
		$defaultExpireDateEnforced = \OCP\Util::isDefaultExpireDateEnforced();
		if ($defaultExpireDateEnforced && $shareTime === null) {
			$items = self::getItemShared($itemType, $itemSource);
			$firstItem = reset($items);
			$shareTime = (int)$firstItem['stime'];
		}

		if ($defaultExpireDateEnforced) {
			// initialize max date with share time
			$maxDate = new \DateTime();
			$maxDate->setTimestamp($shareTime);
			$maxDays = \OC::$server->getConfig()->getAppValue('core', 'shareapi_expire_after_n_days', '7');
			$maxDate->add(new \DateInterval('P' . $maxDays . 'D'));
			if ($date > $maxDate) {
				$warning = 'Cannot set expiration date. Shares cannot expire later than ' . $maxDays . ' after they have been shared';
				$warning_t = $l->t('Cannot set expiration date. Shares cannot expire later than %s after they have been shared', array($maxDays));
				self::log('NextNote\Fixtures\ShareFix', $warning, \OCP\Util::WARN);
				throw new \Exception($warning_t);
			}
		}

		if ($date < $today) {
			$message = 'Cannot set expiration date. Expiration date is in the past';
			$message_t = $l->t('Cannot set expiration date. Expiration date is in the past');
			self::log('NextNote\Fixtures\ShareFix', $message, \OCP\Util::WARN);
			throw new \Exception($message_t);
		}

		return $date;
	}

}