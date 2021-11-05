<?php

require_once __DIR__ . '/chat.php';

/**
 * Multi-dialog chat class
 * Have negative id.
*/

class Conversation extends Chat
{
	private string $title;
	private string $link;

	private bool $is_leaved;
	private bool $is_kicked;
	private bool $is_muted;

	private bool $show_pinned_messages;

	private int $access_level;

	private $permissions;
	private $photo;

	public function __construct (string $localId)
	{
		parent::__construct($localId);

		$this->type = 'conversation';

		$res = $this->currentConnection->prepare("SELECT permissions_level, leaved_time, return_time, permissions, is_leaved, is_kicked, is_muted, title, photo, link, notifications, show_pinned_messages FROM messages.members_chat_list AS cl JOIN messages.members_engine_1 AS cm ON cl.uid = cm.uid WHERE cl.uid = ? AND user_id = ? LIMIT 1");

		if ($res->execute([$this->uid, intval($_SESSION['user_id'])]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if (!$data) return;

			$this->isValid   = true;
			$this->is_leaved = boolval(intval($data['is_leaved']));
			$this->is_kicked = boolval(intval($data['is_kicked']));
			$this->is_muted  = boolval(intval($data['is_muted']));

			$this->access_level = intval($data['permissions_level']);
			$this->title        = strval(trim($data['title']));
			$this->link         = strval($data['link']);
			$this->permissions  = new Data(unserialize($data['permissions']));

			$this->leavedTime   = intval($data['leaved_time']);
			$this->returnedTime = intval($data['return_time']);

			$this->notifications_enabled = boolval(intval($data['notifications']));
			$this->show_pinned_messages  = boolval(intval($data['show_pinned_messages']));

			if (!is_empty($data['photo']))
			{
				$attachment = (new AttachmentsParser())->resolveFromQuery($data['photo']);

				if ($attachment && $attachment->valid())
					$this->photo = $attachment;
			}
		}
	}

	public function getPermissions (): Data
	{
		return $this->permissions;
	}

	public function removeUser (int $entity_id): int
	{
		if (!$this->valid()) return 0;
		if ($this->isKicked() || $this->isLeaved()) return 0;

		if ($entity_id === intval($_SESSION['user_id']))
		{
			if (!$this->isLeaved())
			{
				if (
					$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_leaved = 1 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([intval($_SESSION['user_id']), $this->uid]) &&
					$this->currentConnection->prepare("UPDATE messages.members_chat_list SET leaved_time = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([time(), intval($_SESSION['user_id']), $this->uid]) &&
					$this->sendServiceMessage("leaved_chat") >= 0
				) {
					$this->is_kicked = false;
					$this->is_leaved = true;

					return 1;
				} else
				{
					return 0;
				}
			}

			return -1;
		} else
		{
			if ($this->isLeaved()) return 0;
			if ($this->getPermissions()->can_kick > $this->getAccessLevel()) return -2;

			$member = $this->findMemberById($entity_id);
			if (!$member || $member->is_kicked) return -1;

			if (!$member->is_leaved)
			{
				if ($member->access_level >= $this->getAccessLevel()) return -2;
			}

			if ($member)
			{
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_leaved = 1 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$entity_id, $this->uid]);
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_kicked = 1 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$entity_id, $this->uid]);
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET leaved_time = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([time(), $entity_id, $this->uid]);
			}

			if ($this->sendServiceMessage('kicked_user', $entity_id) >= 0)
			{
				return 1;
			}

			return 0;
		}
	}

	public function addUser (int $entity_id): int
	{
		if (!$this->valid()) return 0;
		if ($this->isKicked()) return 0;

		if ($entity_id === intval($_SESSION['user_id']))
		{
			if ($this->isLeaved())
			{
				if (
					$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_leaved = 0 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([intval($_SESSION['user_id']), $this->uid]) &&
					$this->currentConnection->prepare("UPDATE messages.members_chat_list SET return_time = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([time(), intval($_SESSION['user_id']), $this->uid]) &&
					$this->sendServiceMessage("returned_to_chat") >= 0
				) {
					$this->is_kicked = false;
					$this->is_leaved = false;

					return 1;
				} else
				{
					return 0;
				}
			}

			return -1;
		} else {
			if ($this->isLeaved()) return 0;

			if ($this->getPermissions()->can_invite > $this->getAccessLevel()) return -2;

			$entity = Entity::findById($entity_id);
			if (!$entity || !$entity->valid() || $entity->isBanned()) return -3;

			if (!$entity->canInviteToChat())
			{
				return -5;
			}

			$member = $this->findMemberById($entity_id);
			if ($member && !$member->is_leaved && !$member->is_kicked) return -1;

			if ($member)
			{
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_leaved = 0 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$entity_id, $this->uid]);
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_kicked = 0 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$entity_id, $this->uid]);
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET invited_by = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([intval($_SESSION['user_id']), $entity_id, $this->uid]);
				$this->currentConnection->prepare("UPDATE messages.members_chat_list SET return_time = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([time(), $entity_id, $this->uid]);
			} else
			{
				$res = $this->currentConnection->prepare("SELECT lid FROM messages.members_chat_list WHERE user_id = ? ORDER BY lid LIMIT 1");
				if ($res->execute([$entity_id]))
				{
					$new_local_chat_id = intval($res->fetch(PDO::FETCH_ASSOC)['lid']) - 1;
					
					if (!$this->currentConnection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, invited_by, return_time, leaved_time, last_time, is_kicked, is_leaved) VALUES (?, ?, ?, 0, ?, ?, ?, ?, 0, 0)')->execute([$entity_id, $new_local_chat_id, $this->uid, intval($_SESSION['user_id']), time(), time(), time()]))
					{
						return -9;
					}
				} else {
					return -8;
				}
			}

			if ($this->sendServiceMessage('invited_user', $entity_id) > 0)
			{
				return 1;
			}

			return 0;
		}
	}

	public function changeOwnerShip (int $entity_id): bool
	{
		if ($this->getAccessLevel() !== 9) return false;
		if ($entity_id === intval($_SESSION['user_id'])) return false;

		$member = $this->findMemberById($entity_id);
		if (!$member || $member->is_kicked || $member->is_leaved) return false;

		if (
			$this->currentConnection->prepare("UPDATE messages.members_chat_list SET permissions_level = 9 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$entity_id, $this->uid]) &&
			$this->currentConnection->prepare("UPDATE messages.members_chat_list SET permissions_level = 8 WHERE user_id = ? AND uid = ? LIMIT 1")->execute([intval($_SESSION['user_id']), $this->uid])
		)
		{
			$user_ids  = [];
			$local_ids = [];

			$members = $this->getMembers();
			foreach ($members as $index => $member_info) {
				$user_ids[]  = $member_info->user_id;
				$local_ids[] = $member_info->local_id;
			}

			if ($this->sendEvent($user_ids, $local_ids, [
				'event' => 'chat_event',
				'type'  => 'updated_user_permissions_level',
				'data'  => [
					'updater_id' => intval($_SESSION['user_id']),
					'entity_id'  => $entity_id,
					'new_level'  => 9
				]
			])) return true;
		}

		return false;
	}

	public function toggleWriteAccess (int $entity_id): int
	{
		if (!$this->valid()) return 0;
		if ($this->isKicked() || $this->isLeaved()) return 0;
		if ($entity_id === intval($_SESSION['user_id'])) return -1;

		if ($this->getPermissions()->can_mute > $this->getAccessLevel()) return -2;

		$member = $this->findMemberById($entity_id);
		if (!$member || $member->is_kicked || $member->is_leaved) return -3;
		if ($member->access_level >= $this->getAccessLevel()) return -4;

		$new_state = intval(!$member->is_muted);
		if ($this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_muted = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$new_state, $entity_id, $this->uid]))
		{
			if ($this->sendServiceMessage($new_state ? "mute_user" : "unmute_user", $entity_id)) return 1;
		}

		return 0;
	}

	public function setUserPermissionsLevel (int $entity_id, int $new_permissions_level): int
	{
		if (!$this->valid()) return false;
		if ($this->isKicked() || $this->isLeaved()) return false;
		if ($this->getPermissions()->can_change_levels > $this->getAccessLevel()) return false;
	
		$member = $this->findMemberById($entity_id);
		if (!$member || $member->is_kicked || $member->is_leaved) return false;

		if ($member->user_id === intval($_SESSION['user_id'])) return false;

		if ($member->access_level >= $this->getAccessLevel()) return false;
		if ($new_permissions_level < 0 || $new_permissions_level >= $this->getAccessLevel()) return false;

		if ($this->currentConnection->prepare("UPDATE messages.members_chat_list SET permissions_level = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$new_permissions_level, $entity_id, $this->uid]))
		{
			$user_ids  = [];
			$local_ids = [];

			$members = $this->getMembers();
			foreach ($members as $index => $member_info) {
				$user_ids[]  = $member_info->user_id;
				$local_ids[] = $member_info->local_id;
			}

			if ($this->sendEvent($user_ids, $local_ids, [
				'event' => 'chat_event',
				'type'  => 'updated_user_permissions_level',
				'data'  => [
					'updater_id' => intval($_SESSION['user_id']),
					'entity_id'  => $entity_id,
					'new_level'  => $new_permissions_level
				]
			])) return true;
		}

		return false;
	}

	public function isPinnedMessageShown (): bool
	{
		return $this->show_pinned_messages;
	}

	public function setPinnedMessageShown (): bool
	{
		$this->show_pinned_messages = !$this->show_pinned_messages;

		return $this->currentConnection->prepare("UPDATE messages.members_chat_list SET show_pinned_messages = ? WHERE uid = ? AND user_id = ? LIMIT 1")->execute([intval($this->show_pinned_messages), $this->uid, intval($_SESSION['user_id'])]);
	}

	public function getInviteLink (): string
	{
		if ($this->getAccessLevel() !== 9) return '';

		return is_empty($this->link) ? '' : Project::DEFAULT_URL . '/chats?c=' . $this->link;
	}

	public function updateInviteLink (): bool
	{
		if (!$this->valid()) return false;
		if ($this->isKicked() || $this->isLeaved()) return false;
		if ($this->getAccessLevel() !== 9) return false;

		$new_link_query = str_shuffle('asdaAJKDSADLKN/asdklasdlqwek/djghkuwefldlkwASDLKJWQD');

		if ($this->currentConnection->prepare("UPDATE messages.members_engine_1 SET link = ? WHERE uid = ? LIMIT 1")->execute([$new_link_query, $this->uid]))
		{
			$this->link = $new_link_query;

			$user_ids  = [];
			$local_ids = [];

			$members = $this->getMembers();
			foreach ($members as $index => $member_info) {
				$user_ids[]  = $member_info->user_id;
				$local_ids[] = $member_info->local_id;
			}

			if ($this->sendEvent($user_ids, $local_ids, [
				'event' => 'chat_event',
				'type'  => 'updated_invite_link',
				'data'  => [
					'user_id'  => intval($_SESSION['user_id']),
					'new_link' => $this->getInviteLink()
				]
			])) return true;
		}

		return false;
	}

	public function setPermissionsValue (string $permissions_group, int $new_value): bool
	{
		if (!$this->valid()) return false;
		if ($this->isKicked() || $this->isLeaved()) return false;
		if ($this->getAccessLevel() !== 9) return false;

		$permissions = [
			'can_change_title'  => $this->getPermissions()->can_change_title,
			'can_change_photo'  => $this->getPermissions()->can_change_photo,
			'can_kick'          => $this->getPermissions()->can_kick,
			'can_invite'        => $this->getPermissions()->can_invite,
			'can_invite_bots'   => $this->getPermissions()->can_invite_bots,
			'can_mute'          => $this->getPermissions()->can_mute,
			'can_pin_message'   => $this->getPermissions()->can_pin_message,
			'delete_messages_2' => $this->getPermissions()->delete_messages_2,
			'can_change_levels' => $this->getPermissions()->can_change_levels,
			'can_link_join'     => $this->getPermissions()->can_link_join
		];

		if (!isset($permissions[$permissions_group])) return false;
		if ($new_value < 0 || $new_value > 9) return false;

		$permissions[$permissions_group] = $new_value;
		if ($this->currentConnection->prepare("UPDATE messages.members_engine_1 SET permissions = ? WHERE uid = ? LIMIT 1")->execute([serialize($permissions), $this->uid]))
		{
			$this->getPermissions()->{$permissions_group} = $new_value;

			$user_ids  = [];
			$local_ids = [];

			$members = $this->getMembers();
			foreach ($members as $index => $member_info) {
				$user_ids[]  = $member_info->user_id;
				$local_ids[] = $member_info->local_id;
			}

			if ($this->sendEvent($user_ids, $local_ids, [
				'event' => 'chat_event',
				'type'  => 'updated_permissions_group',
				'data'  => [
					'user_id'           => intval($_SESSION['user_id']),
					'permissions_group' => $permissions_group,
					'new_value'         => $new_value
				]
			])) return true;
		}

		return false;
	}

	public function setTitle (string $newTitle): int
	{
		if (!$this->valid()) return 0;
		if ($this->isKicked() || $this->isLeaved()) return 0;
		if ($this->getPermissions()->can_change_title > $this->getAccessLevel()) return -1;

		if (is_empty(trim($newTitle)) || strlen(trim($newTitle)) > 64) return -2;
		
		if ($this->currentConnection->prepare("UPDATE messages.members_engine_1 SET title = ? WHERE uid = ? LIMIT 1")->execute([trim($newTitle), $this->uid]))
		{
			if ($this->sendServiceMessage("change_title", NULL, NULL, $newTitle) > 0) return 1;
		}

		return 0;
	}

	public function getTitle (): string
	{
		return $this->title;
	}

	public function setPhoto (?Photo $photo = NULL): int
	{
		if (!$this->valid()) return 0;
		if ($this->isKicked() || $this->isLeaved()) return 0;

		if ($this->getPermissions()->can_change_photo > $this->getAccessLevel()) return -1;
		if ($photo && !$photo->valid()) return -2;

		$this->photo = $photo;
		if ($photo)
		{
			if ($this->currentConnection->prepare("UPDATE messages.members_engine_1 SET photo = ? WHERE uid = ? LIMIT 1")->execute([$photo->getQuery(), $this->uid]))
			{
				if ($this->sendServiceMessage("updated_photo", NULL, $photo->getLink()) > 0) return 1;
			}
		} else
		{
			if ($this->currentConnection->prepare("UPDATE messages.members_engine_1 SET photo = '' WHERE uid = ? LIMIT 1")->execute([$this->uid]))
			{
				if ($this->sendServiceMessage("deleted_photo") > 0) return 1;
			}
		}

		return 0;
	}

	public function isLeaved (): bool
	{
		return $this->is_leaved;
	}

	public function isKicked (): bool
	{
		return $this->is_kicked;
	}

	public function isMuted (): bool
	{
		return $this->is_muted;
	}

	public function getPhoto (): ?photo
	{
		return $this->photo;
	}

	public function getAccessLevel (): int
	{
		return $this->access_level;
	}

	public function findMemberById (int $entity_id, bool $extended = false): ?Data
	{
		$res = $this->currentConnection->prepare("SELECT user_id, lid, permissions_level, invited_by, is_muted, is_leaved, is_kicked FROM messages.members_chat_list WHERE user_id = ? AND uid = ? ORDER BY permissions_level DESC LIMIT 1");

		if ($res->execute([$entity_id, $this->uid]))
		{
			$row = $res->fetch(PDO::FETCH_ASSOC);
			if ($row)
			{
				$user_info = new Data([
					'local_id'     => intval($row['lid']),
					'access_level' => intval($row['permissions_level']),
					'user_id'      => intval($row['user_id']),
					'invited_by'   => intval($row['invited_by']),
					'is_muted'     => intval($row['is_muted']),
					'is_leaved'    => intval($row['is_leaved']),
					'is_kicked'    => intval($row['is_kicked'])
				]);

				if ($extended)
				{
					$entity = Entity::findById($user_info->user_id);
					if ($entity && $entity->valid())
						$user_info->entity = $entity;

					$invited_by_entity = Entity::findById($user_info->invited_by);
					if ($invited_by_entity && $invited_by_entity->valid())
						$user_info->invited = $invited_by_entity;
				}

				return $user_info;
			}
		}

		return NULL;
	}

	public function getMembers (bool $extended = false): array
	{
		$result = [];

		if (!$this->valid()) return $result;
		if ($this->isKicked() || $this->isLeaved()) return $result;

		$res = $this->currentConnection->prepare("SELECT user_id, lid, permissions_level, invited_by, is_muted FROM messages.members_chat_list WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 ORDER BY permissions_level DESC");

		if ($res->execute([$this->uid]))
		{
			$info = $res->fetchAll(PDO::FETCH_ASSOC);
			foreach ($info as $index => $row) {
				$user_info = new Data([
					'local_id'     => intval($row['lid']),
					'access_level' => intval($row['permissions_level']),
					'user_id'      => intval($row['user_id']),
					'invited_by'   => intval($row['invited_by']),
					'is_muted'     => intval($row['is_muted'])
				]);

				if ($extended)
				{
					$entity = Entity::findById($user_info->user_id);
					if ($entity && $entity->valid())
						$user_info->entity = $entity;

					$invited_by_entity = Entity::findById($user_info->invited_by);
					if ($invited_by_entity && $invited_by_entity->valid())
						$user_info->invited = $invited_by_entity;
				}

				$result[] = $user_info;
			}
		}

		return $result;
	}

	public function getMessages (int $count = 100, int $offset = 0): array
	{
		$result = [];

		if ($this->uid === 0) return $result;

		if ($count < 1) $count = 1;
		if ($count > 1000) $count = 1000;
		if ($offset < 0) $offset = 0;

		$query = 'SELECT local_chat_id FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > ".$cleared_message_id." AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) AND uid = '.$this->uid.' ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count.';';

		if ($this->isKicked() || $this->isLeaved())
		{
			$query = 'SELECT local_chat_id FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > (SELECT cleared_message_id FROM messages.members_chat_list WHERE user_id = '.intval($_SESSION['user_id']).' AND uid = '.$this->uid.' LIMIT 1) AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) AND uid = '.$this->uid.' AND time <= '.$this->leavedTime.' ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count.';';
		} else {
			if (!$this->isKicked() && $this->returnedTime !== 0) 
			{
				$query = 'SELECT local_chat_id FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > (SELECT cleared_message_id FROM messages.members_chat_list WHERE user_id = '.intval($_SESSION['user_id']).' AND uid = '.$this->uid.' LIMIT 1) AND uid = '.$this->uid.' AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) AND (time <= '.$this->leavedTime.' OR time >= '.$this->returnedTime.') ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count.';';
							
				if ($this->leavedTime === 0)
				{
					$query = 'SELECT local_chat_id FROM messages.chat_engine_1 WHERE (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) AND deleted_for_all != 1 AND local_chat_id > (SELECT cleared_message_id FROM messages.members_chat_list WHERE user_id = '.intval($_SESSION['user_id']).' AND uid = '.$this->uid.' LIMIT 1) AND uid = '.$this->uid.' OR uid = '.$this->uid.' AND time >= '.$this->returnedTime.' AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count.';';
				}
			}
		}

		$res = $this->currentConnection->prepare($query);
		if ($res->execute())
		{
			$local_chat_ids = $res->fetchAll(PDO::FETCH_ASSOC);

			foreach ($local_chat_ids as $index => $local_info) {
				$message_id = intval($local_info['local_chat_id']);

				$message = new Message($this, $message_id);
				if ($message->valid() && !$message->isDeleted())
					$result[] = $message;
			}
		}

		return array_reverse($result);
	}

	public function canWrite (): int
	{
		if (!$this->valid())   return 0;
		if ($this->isKicked()) return 0;
		if ($this->isMuted())  return -1;
		if ($this->isLeaved()) return -2;

		return 1;
	}
}

?>