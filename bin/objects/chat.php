<?php

require_once __DIR__ . '/../functions/messages.php';
require_once __DIR__ . '/message.php';

/**
 * Base chat class.
 * Used for dialog and conversations
*/

abstract class Chat extends EventEmitter
{
	protected int $uid;
	protected int $peer_id;
	protected int $lastReadMsgId;

	protected bool $isValid;
	protected bool $is_read;

	protected bool $notifications_enabled;

	protected string $type;

	protected $currentConnection;

	abstract public function canWrite (): int;

	public function __construct (string $localId)
	{
		$this->isValid = false;
		$this->currentConnection = DataBaseManager::getConnection();

		$this->notifications_enabled = true;
		$this->is_read               = true;
		$this->type                  = 'chat';

		$is_bot_dialog = false;
		$is_multi_chat = false;
		$local_chat_id = 0;

		if (substr($localId, 0, 1) === "b")
		{
			$is_bot_dialog = true;
			$is_multi_chat = false;
			$local_chat_id = intval(substr($localId, 1, strlen($localId))) * -1;
		} else if (intval($localId) < 0)
		{
			$is_bot_dialog = false;
			$is_multi_chat = true;
			$local_chat_id = intval($localId);
		} else if (intval($localId) > 0)
		{
			$is_bot_dialog = false;
			$is_multi_chat = false;
			$local_chat_id = intval($localId);
		}

		$res = $this->currentConnection->prepare($is_bot_dialog ? "SELECT is_read, last_read_message_id, uid FROM messages.members_chat_list WHERE lid = ? AND uid > 0 AND user_id = ? LIMIT 1" : "SELECT is_read, last_read_message_id, uid FROM messages.members_chat_list WHERE lid = ? AND user_id = ? LIMIT 1");

		if ($res->execute([$local_chat_id, $_SESSION['user_id']]))
		{
			$result = $res->fetch(PDO::FETCH_ASSOC);
			if (isset($result['uid']))
			{
				$this->uid = intval($result['uid']);
			} else
			{
				$this->uid = 0;
			}

			$this->peer_id       = intval($local_chat_id);
			$this->is_read       = intval($result['is_read']);
			$this->lastReadMsgId = intval($result['last_read_message_id']);
		}
	}

	public function getLastReadMessageId (): int
	{
		return $this->lastReadMsgId;
	}

	public function getUID (): int
	{
		return $this->uid ? $this->uid : 0;
	}

	public function getLocalPeerId (): int
	{
		return $this->peer_id;
	}

	public function getType (): string
	{
		return $this->type;
	}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	public function isNotificationsEnabled (): bool
	{
		return $this->notifications_enabled;
	}

	public function setNotificationsEnabled (): bool
	{
		$this->notifications_enabled = !$this->notifications_enabled;

		return $this->currentConnection->prepare("UPDATE messages.members_chat_list SET notifications = ? WHERE uid = ? AND user_id = ? LIMIT 1")->execute([intval($this->notifications_enabled), $this->uid, intval($_SESSION['user_id'])]);
	}

	public function clear (): bool
	{
		$last_message = $this->getLastMessage();
		if ($last_message)
		{
			$last_message_id = $last_message->getId();
			$this->read($last_message_id);

			if ($this->currentConnection->prepare("UPDATE messages.members_chat_list SET hidden = 1, cleared_message_id = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$last_message_id, intval($_SESSION['user_id']), $this->uid]))
			{
				$event = [
					'event' => 'cleared_chat'
				];

				if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
				{
					$event['bot_peer_id'] = $this->getCompanion()->getId() * -1;
				} else
				{
					$event['peer_id'] = $this->getLocalPeerId();
				}

				return $this->sendEvent([intval($_SESSION['user_id'])], [0], $event);
			}
		}

		return false;
	}

	public function isRead (): bool
	{
		return $this->is_read;
	}

	public function read (int $messageId = 0): bool
	{
		$last_read_message_id = $messageId <= 0 ? $this->getLastReadMessageId() : $messageId;

		if (
			$this->currentConnection->prepare("UPDATE messages.members_chat_list SET is_read = 1, last_read_message_id = ? WHERE user_id = ? AND uid = ? LIMIT 1000")->execute([$last_read_message_id, intval($_SESSION['user_id']), $this->uid]) &&
			$this->currentConnection->prepare("UPDATE messages.members_chat_list SET hidden = 0 WHERE uid = ? AND user_id = ? AND is_leaved = 0 AND is_kicked = 0 LIMIT 1000")->execute([$this->uid, intval($_SESSION['user_id'])])
		)
		{
			if (intval($_SESSION['user_id']) < 0)
				return true;

			$event = [
				'event' => 'dialog_read'
			];

			if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
			{
				$event['bot_peer_id'] = $this->getCompanion()->getId() * -1;
			} else
			{
				$event['peer_id'] = $this->getLocalPeerId();
			}

			return $this->sendEvent([intval($_SESSION['user_id'])], [0], $event);
		}

		return false;
	}

	public function sendMessage (string $text = '', string $attachments = '', string $fwd = '', string $payload = ''): int
	{
		$can_write_messages = $this->canWrite();

		if ($can_write_messages === 0) return -1;
		if ($can_write_messages === -1) return -2;
		if ($this->getType() === 'conversation' && $can_write_messages === -2)
		{
			if (!$this->addUser(intval($_SESSION['user_id']))) return -1;
		}

		$attachments_list = (new AttachmentsParser())->getObjects($attachments);

		if (is_empty($text) && count($attachments_list) === 0) return -3;

		if (strlen($text) > 4096)
		{
			$text_list = explode_length($text, 4096);
			foreach ($text_list as $index => $text_part) {
				if ($index >= 10) break;

				if ($index === (count($text_list) - 1))
					return $this->sendMessage($text_part, $attachments, $fwd, $payload);
				else
					$this->sendMessage($text_part);
			}
		}
		if (count($attachments_list) > 10) return -5;

		$done_text    = trim($text);
		$current_time = time();
		$done_atts    = '';

		foreach ($attachments_list as $key => $value) {
			$done_atts .= $value->getCredentials();
		}

		if (!$this->uid && $this->getType() === 'dialog')
		{
			$this->uid = get_last_uid() + 1;
			if (!$this->uid)return -6;

			$companion_id = $this->getCompanion()->getId();

			if (!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([intval($_SESSION['user_id']), $companion_id, $this->uid, $current_time]) || 
				!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([$companion_id, intval($_SESSION['user_id']), $this->uid, $current_time])
			) return -7;
		} else if (!$this->uid && $this->getType() === 'conversation')
		{
			return -10;
		}

		$companion_id = $this->getType() === 'dialog' ? ($this->getCompanion()->getType() === 'bot' ? $this->getCompanion()->getId() * -1 : $this->getCompanion()->getId()) : 0;

		$local_message_id = intval(get_local_chat_id($this->uid));
		if (!$local_message_id) return -8;

		$res = $this->currentConnection->prepare("INSERT INTO messages.chat_engine_1 (
			uid, owner_id, local_chat_id, text, attachments, reply, time, flags, to_id
		) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");

		if (!$res->execute([
			$this->uid,
			intval($_SESSION['user_id']),
			$local_message_id,
			$done_text,
			'',
			$done_atts,
			$current_time,
			$companion_id
		])) return -9;

		$atts_array = [];
		foreach ($attachments_list as $index => $attachment)
		{
			$atts_array[] = $attachment->toArray();
		}

		$event = [
			'event' => 'new_message',
			'message' => [
				'from_id'     => intval($_SESSION['user_id']),
				'id'          => $local_message_id,
				'type'        => 'message',
				'text'        => $done_text,
				'time'        => $current_time,
				'attachments' => $atts_array,
				'fwd'         => []
			],
			'uid' => $this->uid
		];

		if (!is_empty($payload) && strlen($payload) < 1024)
			$event['payload'] = $payload;

		$user_ids  = [];
		$local_ids = [];

		if ($this->getType() === 'dialog')
		{
			if (intval($_SESSION['user_id']) === $this->getCompanion()->getId())
			{
				$user_ids  = [intval($_SESSION['user_id'])];
				$local_ids = [intval($_SESSION['user_id'])];
			} else
			{
				$user_ids  = [intval($_SESSION['user_id']), $companion_id];
				$local_ids = [$companion_id, intval($_SESSION['user_id'])];
			}

			if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
			{
				if (intval($_SESSION['user_id']) < 0)
					$event['bot_peer_id'] = intval($_SESSION['user_id']);
				if ($companion_id < 0)
					$event['bot_peer_id'] = $companion_id;
			}
		} else
		{
			$member_ids = $this->getMembers();

			foreach ($member_ids as $index => $user_info) {
				$user_ids[]  = $user_info->user_id;
				$local_ids[] = $user_info->local_id;
			}
		}

		$this->sendEvent($user_ids, $local_ids, $event, intval($_SESSION['user_id']));

		$this->currentConnection->prepare("UPDATE messages.members_chat_list SET hidden = 0, is_read = 0, last_time = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0")->execute([$current_time, $this->uid]);

		if (intval($_SESSION['user_id']) > 0 && $this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
		{
			toggle_send_access($this->currentConnection, intval($_SESSION['user_id']), $companion_id);
		}

		return $this->read() ? $local_message_id : $local_message_id;
	}

	public function sendServiceMessage (string $event, ?int $entity_id = NULL, ?string $new_src = NULL, ?string $new_title = NULL): int
	{
		$current_time = time();

		$allowed_events = [
			"mute_user", "unmute_user", "returned_to_chat",
			"join_by_link", "leaved_chat", "updated_photo",
			"deleted_photo", "kicked_user", "invited_user",
			"change_title", "chat_create"
		];

		if (!in_array($event, $allowed_events)) return -15;

		if (!$this->uid && $this->getType() === 'dialog')
		{
			$this->uid = get_last_uid() + 1;
			if (!$this->uid)return -6;

			$companion_id = $this->getCompanion()->getId();

			if (!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([intval($_SESSION['user_id']), $companion_id, $this->uid, $current_time]) || 
				!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([$companion_id, intval($_SESSION['user_id']), $this->uid, $current_time])
			) return -7;
		} else if (!$this->uid && $this->getType() === 'conversation')
		{
			return -10;
		}

		$companion_id = $this->getType() === 'dialog' ? ($this->getCompanion()->getType() === 'bot' ? $this->getCompanion()->getId() * -1 : $this->getCompanion()->getId()) : 0;

		$local_message_id = intval(get_local_chat_id($this->uid));
		if (!$local_message_id) return -8;

		$res = $this->currentConnection->prepare("INSERT INTO messages.chat_engine_1 (
			uid, owner_id, local_chat_id, text, attachments, reply, time, flags, to_id, event, new_src, new_title
		) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)");

		if (!$res->execute([
			$this->uid,
			intval($_SESSION['user_id']),
			$local_message_id,
			'',
			'',
			'',
			$current_time,
			$entity_id,
			$event,
			$new_src ? $new_src : '',
			$new_title ? $new_title : ''
		])) return -9;

		$event = [
			'event' => 'new_message',
			'message' => [
				'from_id' => intval($_SESSION['user_id']),
				'id'      => $local_message_id,
				'type'    => 'service_message',
				'time'    => $current_time,
				'action' => [
					'type' => strtolower($event),
				]
			]
		];

		if ($entity_id)
			$event['message']['action']['to_id'] = $entity_id;
		if ($new_src)
			$event["message"]["action"]["new_photo_url"] = $new_src;
		if ($new_title)
			$event["message"]["action"]["new_title"] = $new_title;

		$user_ids  = [];
		$local_ids = [];

		if ($this->getType() === 'dialog')
		{
			if (intval($_SESSION['user_id']) === $this->getCompanion()->getId())
			{
				$user_ids  = [intval($_SESSION['user_id'])];
				$local_ids = [intval($_SESSION['user_id'])];
			} else
			{
				$user_ids  = [intval($_SESSION['user_id']), $companion_id];
				$local_ids = [$companion_id, intval($_SESSION['user_id'])];
			}

			if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
			{
				if (intval($_SESSION['user_id']) < 0)
					$event['bot_peer_id'] = intval($_SESSION['user_id']);
				if ($companion_id < 0)
					$event['bot_peer_id'] = $companion_id;
			}
		} else
		{
			$member_ids = $this->getMembers();

			foreach ($member_ids as $index => $user_info) {
				$user_ids[]  = $user_info->user_id;
				$local_ids[] = $user_info->local_id;
			}
		}

		$this->sendEvent($user_ids, $local_ids, $event, intval($_SESSION['user_id']));

		$this->currentConnection->prepare("UPDATE messages.members_chat_list SET hidden = 0, is_read = 0, last_time = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0")->execute([$current_time, $this->uid]);

		return $local_message_id;
	}

	public function findMessageById (int $messageId): ?Message
	{
		$message = new Message($this, $messageId);

		if ($message->valid() && !$message->isDeleted())
			return $message;

		return NULL;
	}

	public function toArray (): array
	{
		$chat = [];

		if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
		{
			$chat['bot_peer_id'] = $this->getCompanion()->getId() * -1;
		} else
		{
			$chat['peer_id'] = $this->getLocalPeerId();
		}

		$last_message = $this->getLastMessage();

		$chat['metadata'] = [
			'is_read_by_me' => intval($this->isRead()),
			'notifications' => intval($this->isNotificationsEnabled())
		];

		if (!$this->isRead())
			$chat['metadata']['unread_count'] = $last_message ? ($last_message->getId() - $this->getLastReadMessageId()) : 0;

		if ($last_message)
			$chat['last_message'] = $last_message->toArray();

		$chat['chat_info'] = [
			'is_multi_chat' => intval($this->getType() === 'conversation'),
			'is_bot_chat'   => intval($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot'),
			'data'          => []
		];

		if ($this->getType() === 'dialog')
		{
			$chat['chat_info']['data'] = $this->getCompanion()->toArray('*');
		}
		if ($this->getType() === 'conversation')
		{
			if ($this->isPinnedMessageShown())
				$chat['metadata']['show_pinned_messages'] = 1;

			$chat['metadata']['permissions'] = [
				'is_kicked' => intval($this->isKicked()),
				'is_leaved' => intval($this->isLeaved()),
				'is_muted'  => intval($this->isMuted())
			];

			$chat['chat_info']['data'] = [
				'access_level' => $this->getAccessLevel(),
				'permissions'  => json_decode(json_encode($this->getPermissions()), true),
				'title'        => $this->getTitle(),
				'photo_url'    => $this->getPhoto() ? $this->getPhoto()->getLink() : Project::getDevDomain() . '/images/default.png'
			];

			if (!$this->isKicked() && !$this->isLeaved())
				$chat['chat_info']['data']['members_count'] = count($this->getMembers());
		}

		return $chat;
	}

	public function getLastMessage (): ?Message
	{
		if (!method_exists($this, 'getMessages')) return NULL;

		$message = $this->getMessages(1)[0];

		return $message ? $message : NULL;
	}

	////////////////////////////////////////////
	public static function getList (int $count = 30, int $offset = 0, int $showOnly = 0): array
	{
		$result = [];

		if ($showOnly < 0 || $showOnly > 2) $showOnly = 0;
		if ($count > 100) $count = 100;
		if ($count < 0) $count = 1;
		if ($offset < 0) $offset = 0;

		$connection = DataBaseManager::getConnection();

		/**
		 * showOnly
		 * 0 - all
		 * 1 - conversations
		 * 2 - dialogs
		*/

		$query = "SELECT DISTINCT uid, lid, last_time FROM messages.members_chat_list WHERE hidden = 0 AND user_id = ? AND lid != 0 ORDER BY last_time DESC LIMIT ".intval($offset).",".intval($count);

		if ($showOnly === 1)
			$query = "SELECT DISTINCT uid, lid, last_time FROM messages.members_chat_list WHERE hidden = 0 AND user_id = ? AND lid != 0 AND uid < 0 ORDER BY last_time DESC LIMIT ".intval($offset).",".intval($count);
		if ($showOnly === 2)
			$query = "SELECT DISTINCT uid, lid, last_time FROM messages.members_chat_list WHERE hidden = 0 AND user_id = ? AND lid != 0 AND uid > 0 ORDER BY last_time DESC LIMIT ".intval($offset).",".intval($count);

		$res = $connection->prepare($query);
		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$local_chat_ids = $res->fetchAll(PDO::FETCH_ASSOC);

			foreach ($local_chat_ids as $index => $local_dialog_id) {
				$uid = intval($local_dialog_id['uid']);
				$lid = intval($local_dialog_id['lid']);

				$resulted_local_id = $uid > 0 && $lid < 0 ? ("b" . $lid * -1) : $lid;

				$dialog = self::findById($resulted_local_id);
				if ($dialog)
					$result[] = $dialog;
			}
		}

		return $result;
	}

	public static function create (string $title, array $users_list, ?Photo $photo, ?array $permissions_list = []): int
	{
		$title = trim($title);
		if (is_empty($title) || strlen($title) > 64) return -1;

		$creator_id = intval($_SESSION['user_id']);
		if ($creator_id <= 0) return -3;

		$resulted_users = [$creator_id];
		foreach ($users_list as $index => $user_id) 
		{
			if ($index > 500) break;

			if (!in_array($user_id, $resulted_users))
				$resulted_users[] = intval($user_id);
		}

		$url = $photo && $photo->valid() ? $photo->getQuery() : '';

		if (count($resulted_users) < 2 || count($resulted_users) > 1000)
			return -2;

		$permissions = [
			'can_change_title'  => 4,
			'can_change_photo'  => 4,
			'can_kick'          => 7,
			'can_invite'        => 7,
			'can_invite_bots'   => 8,
			'can_mute'          => 5,
			'can_pin_message'   => 4,
			'delete_messages_2' => 7,
			'can_change_levels' => 9,
			'can_link_join'     => 0
		];

		foreach ($permissions as $permissionName => $value)
		{
			if (isset($permissions_list[$permissionName]))
			{
				if (intval($permissions_list[$permissionName]) >= 0 && intval($permissions_list[$permissionName]) <= 9) 
					$permissions[$permissionName] = intval($permissions_list[$permissionName]);
			}
		}

		$uid = get_last_uid(false);
		if (!$uid) return -4;

		$connection = DataBaseManager::getConnection();
		if ($connection->prepare("INSERT INTO messages.members_engine_1 (uid, title, permissions, photo) VALUES (?, ?, ?, ?)")->execute([
			$uid,
			$title,
			serialize($permissions),
			$url
		]))
		{
			$my_local_chat_id = 0;

			foreach ($resulted_users as $index => $user_id) 
			{
				$entity = Entity::findById($user_id);
				if (!$entity || $entity->isBanned()) continue;
				if (!$entity->canInviteToChat()) continue;

				$user_permissions_level = $user_id === $creator_id ? 9 : 0;

				$res = $connection->prepare('SELECT lid FROM messages.members_chat_list WHERE user_id = ? ORDER BY lid LIMIT 1');
				if ($res->execute([$user_id]))
				{
					$lid = intval($res->fetch(PDO::FETCH_ASSOC)["lid"]) - 1;
					if ($user_id === intval($_SESSION['user_id']))
						$my_local_chat_id = $lid;

					$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, invited_by, permissions_level, last_time) VALUES (?, ?, ?, 0, ?, ?, ?)')->execute([$user_id, $lid, $uid, $creator_id, $user_permissions_level, time()]);
				}
			}

			$chat = Chat::findById($my_local_chat_id);
			if (!$chat || !$chat->valid()) return -8;

			if ($chat->sendServiceMessage("chat_create", $creator_id, NULL, $title) >= 0)
				return $my_local_chat_id * -1;
		}

		return -7;
	}

	public static function findById (string $localId): ?Chat
	{
		require_once __DIR__ . '/conversation.php';
		require_once __DIR__ . '/dialog.php';

		$dialog = intval($localId) < 0 ? new Conversation($localId) : new Dialog($localId);

		if ($dialog->valid())
			return $dialog;

		return NULL;
	}
}

?>