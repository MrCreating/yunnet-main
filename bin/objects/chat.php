<?php

/**
 * Chat class.
 * Repesenta dual-chats and multichats
*/

if (!class_exists('DataBaseConnection'))
	require __DIR__ . '/../database.php';
if (!class_exists('EventEmitter'))
	require __DIR__ . '/../event_manager.php';
if (!class_exists('Entity'))
	require __DIR__ . '/entities.php';
if (!class_exists('Data'))
	require __DIR__ . '/../bin/data.php';

class Chat extends EventEmitter
{
	private $currentConnection = NULL;

	private $peer_id     = NULL;
	private $is_bot_chat = NULL;
	private $uid         = NULL;
	private $members     = NULL;

	private $last_message = NULL;
	private $can_write    = NULL;

	private $is_leaved   = NULL;
	private $is_kicked   = NULL;
	private $is_muted    = NULL;

	private $leaved_time = NULL;
	private $cleared_msg = NULL;

	private $isValid    = false;

	////////////////////////////////////////////////////////////////////////////////////////////
	public function __construct (string $peer_id)
	{
		$user_id = /*intval($_SESSION['user_id'])*/1;

		$this->currentConnection = new DataBaseConnection();

		$chat_data = $this->parseId($peer_id);
		if (!$chat_data) return;

		$this->peer_id     = $chat_data['chat_id'];
		$this->is_bot_chat = $chat_data['is_bot'];

		$this->uid = $this->getUID();
		if ($this->uid)
			$this->isValid = true;

		$this->members      = $this->getMembers();
		$this->can_write    = $this->canWrite();
		$this->last_message = $this->getLastMessage();
	}

	public function getMembers (): array
	{
		$user_id = /*intval($_SESSION['user_id'])*/1;
		$send_id = $this->getPeerId();
		$is_bot  = $this->isBotChat();

		if ($is_bot)
			$send_id = $send_id * -1;

		if (is_array($this->members))
			return $this->members;

		$result = [];

		if ($this->isMultiChat())
		{
			$res = $this->currentConnection
						->getPDOObject()
						->prepare("SELECT DISTINCT user_id, is_muted, is_leaved, invited_by, leaved_time, is_kicked, permissions_level, lid, cleared_message_id FROM messages.members_chat_list WHERE uid = ? ORDER BY permissions_level DESC;");

			if ($res->execute([$this->uid]))
			{
				$data = $res->fetchAll(PDO::FETCH_ASSOC);

				if ($data)
				{
					foreach ($data as $index => $user_info) 
					{
						$current_user_id = intval($user_info['user_id']);
						$user_object = $current_user_id > 0 ? new User($current_user_id) : new Bot($current_user_id * -1);

						$user_object->chatInfo = new Data([
							'localId'   => intval($user_info["lid"]),
							'invitedBy' => intval($user_info["invited_by"]),
							'flags'   => [
								'isMuted'  => intval($user_info["permissions_level"]) === 9 ? false : intval($user_info["is_muted"]),
								'isLeaved' => intval($user_info["is_leaved"]),
								'isKicked' => intval($user_info["permissions_level"]) === 9 ? false : intval($user_info["is_kicked"]),
								'level'    => intval($user_info["permissions_level"])
							]
						]);

						if ($current_user_id === $user_id)
						{
							$this->is_kicked   = intval($user_info["permissions_level"]) === 9 ? false : boolval(intval($user_info["is_kicked"]));
							$this->is_leaved   = boolval(intval($user_info["is_leaved"]));
							$this->is_muted    = intval($user_info["permissions_level"]) === 9 ? false : boolval(intval($user_info["is_muted"]));
							$this->leaved_time = intval($user_info["leaved_time"]);
							$this->cleared_msg = intval($user_info["cleared_message_id"]);
						}

						$result[$user_object->getId()] = $user_object;
					}
				}
			}
		} else
		{
			$current_user = $user_id > 0 ? new User($user_id) : new Bot($user_id * -1);
			$conversator  = $send_id > 0 ? new User($send_id) : new Bot($send_id * -1);

			$result = [$current_user, $conversator];
		}

		return $result;
	}

	public function canWrite ()
	{
		if (!$this->valid())
			return false;

		if ($this->can_write !== NULL)
			return boolval($this->can_write);

		$user_id = /*intval($_SESSION['user_id'])*/1;
		$send_id = $this->getPeerId();
		$is_bot  = $this->isBotChat();

		if ($is_bot)
			$send_id = $send_id * -1;

		// current user or send destination not exists
		if ($user_id === 0 || $send_id === 0) return false;

		// if it is a not multi-chat or chat not exists and not multi-chat
		if ($this->uid > 0 || $this->uid === NULL)
		{
			// if bot writes to user firstly
			if ($user_id < 0)
			{
				$bot = new Bot($user_id);

				if ($bot->valid())
				{
					$relations = $bot->getRelationsState($send_id);
					if (!$relations || $relations === -1) return false;

					return true;
				}
			} else
			{
				// if user writes to bot or another user

				//$with = $this->getMembers()[1];

				// to bot
				if ($is_bot)
				{
					$with = new Bot($user_id);

					$can_write_to_bot = $with->getSettings()->getValues()->privacy->can_write_messages;

					if ($can_write_to_bot === 2 && ($with->getOwner()->getId() !== $user_id)) return false;

					return true;
				} else
				{
					// to user
					// always can write to itself
					if ($send_id === $user_id) return true;

					$with = new User($send_id);

					if ($with->valid())
					{
						if ($with->isBanned()) return false;

						//if ($with->isBlocked() || $with->inBlacklist()) return false;

						$can_write_to_user = $with->getSettings()->getValues()->privacy->can_write_messages;

						if ($can_write_to_user === NULL || $can_write_to_user === 0) return true;
						if ($can_write_to_user === 1/* && $with->isFriend()*/) return true;
					}
				}
			}
		} else
		{
			// multi-chat
			if ($this->isKicked()) return false;
			if ($this->isLeaved()) return 2;
			if ($this->isMuted()) return 1;

			return true;
		}

		return false;
	}

	public function sendMessage (string $text, array $attachments, array $fwd): bool
	{}

	public function sendServiceMessage (string $eventType, User $destination = NULL): bool
	{}

	public function getMessages (int $offset, int $count): array
	{}

	public function getLastMessage (): ?Message
	{
		if ($this->last_message !== NULL)
			return $this->last_message;

		$connection = $this->currentConnection->getPDOObject();

		$res = $connection->prepare("SELECT DISTINCT uid, leaved_time, return_time, is_leaved, is_kicked, is_muted, cleared_message_id, last_time FROM messages.members_chat_list WHERE uid = ? AND user_id = ? AND lid != 0 ORDER BY last_time DESC LIMIT 1;");

		if ($res->execute([intval($this->uid), /*intval($_SESSION['user_id'])*/1]))
		{
			$chat = $res->fetch(PDO::FETCH_ASSOC);
			if ($chat)
			{
				$uid         = intval($chat["uid"]);
				$leaved_time = intval($chat["leaved_time"]);
				$return_time = intval($chat["return_time"]);
				$is_leaved   = intval($chat["is_leaved"]);
				$is_kicked   = intval($chat["is_kicked"]);
				$is_muted    = intval($chat["is_muted"]);
				$cl_msg_id   = intval($chat["cleared_message_id"]);

				$query = $this->getFinalChatQuery($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, true, $cl_msg_id);

				$res = $connection->prepare($query);
				if ($res->execute())
				{
					$data = $res->fetch(PDO::FETCH_ASSOC)["local_chat_id"];
					if ($data !== NULL)
					{
						$local_id = intval($data);

						return new Message($this, $local_id);
					}
				}
			}
		}

		return NULL;
	}

	public function clear (): bool
	{}

	public function addUser (Entity $user): bool
	{}

	public function removeUser (Entity $user): bool
	{}

	public function toggleWriteAccess (Entity $to): bool
	{}

	public function setTitle (string $newTitle): bool
	{}

	public function setPhoto (?Photo $newPhoto): bool
	{}

	public function getTitle (): ?string
	{}

	public function getPhoto (): ?Photo
	{}

	public function getUID ()
	{
		if ($this->uid !== NULL)
			return $this->uid;

		$connection = $this->currentConnection->getPDOObject();
		$local_id   = $this->getPeerId();
		$is_bot     = $this->isBotChat();
		$user_id    = /*intval($_SESSION['user_id'])*/1;

		if ($is_bot && $local_id > 0)
			$local_id = $local_id * -1;

		$result = $this->isBotChat() ? 
					$connection->prepare("SELECT uid FROM messages.members_chat_list WHERE lid = ? AND uid > 0 AND user_id = ? LIMIT 1;") : 
					$connection->prepare("SELECT uid FROM messages.members_chat_list WHERE lid = ? AND user_id = ? LIMIT 1;");

		if ($result->execute([$local_id, $user_id]))
		{
			$uid = $result->fetch(PDO::FETCH_ASSOC)["uid"];
			if ($uid)
			{
				return intval($uid);
			}
		}

		return $this->uid;
	}

	public function getLeavedTime (): int
	{
		return intval($this->leaved_time);
	}

	public function isKicked (): bool
	{
		if (!$this->isMultiChat()) return false;

		return boolval($this->is_kicked);
	}

	public function isLeaved (): bool
	{
		if (!$this->isMultiChat()) return false;

		return boolval($this->is_leaved);
	}

	public function isMuted (): bool
	{
		if (!$this->isMultiChat()) return false;

		return boolval($this->is_muted);
	}

	public function getPeerId (): int
	{
		return intval($this->peer_id);
	}

	public function isMultiChat (): bool
	{
		return $this->valid() && $this->uid < 0 && !$this->isBotChat();
	}

	public function isBotChat (): bool
	{
		return $this->valid() && $this->is_bot_chat;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function toArray (): array
	{
		return [];
	}
	////////////////////////////////////////////////////////////////////////////////////////////

	///////////////////////////// PRIVATE MESSAGES METHODS /////////////////////////////////////
	private function parseId ($id): ?array
	{
		// default result.
		$result = intval($id);
		$is_bot = false;

		// if it is not integer - it may be a bot chat.
		if ($result === 0)
		{
			$result = intval(explode('b', $id)[1]);

			if ($result > 0) $is_bot = true;
		}

		// if it is already 0 - it is incorrect string!
		if ($result === 0) 
			return NULL;

		// parsed data.
		return ['chat_id' => $result, 'is_bot'  => $is_bot];
	}

	private function getFinalChatQuery ($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, $last_message = true, $cleared_message_id = 0, $offset = 0, $count = 100)
	{
		$user_id = /*intval($_SESSION['user_id'])*/1;

		if ($last_message)
			$last_message = ' DESC LIMIT 1';
		else
			$last_message = ' DESC LIMIT '.intval($offset).', '.intval($count);

		$query = "SELECT local_chat_id/*, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard*/ FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > ".$cleared_message_id." AND (deleted_for NOT LIKE '%".intval($user_id).",%' OR deleted_for IS NULL) AND uid = ".$uid." ORDER BY local_chat_id".$last_message.";";

		if ($uid < 0)
		{
			if ($is_kicked || $is_leaved)
			{
				$query = 'SELECT local_chat_id/*, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard*/ FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND uid = '.$uid.' AND time <= '.$leaved_time.' ORDER BY local_chat_id'.$last_message.';';
			} else {
				if (!$is_leaved && $return_time !== 0) 
				{
					$query = 'SELECT local_chat_id/*, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard*/ FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND uid = '.$uid.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND (time <= '.$leaved_time.' OR time >= '.$return_time.') ORDER BY local_chat_id'.$last_message.';';
								
					if ($leaved_time === 0)
					{
						$query = 'SELECT local_chat_id/*, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard*/ FROM messages.chat_engine_1 WHERE (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND uid = '.$uid.' OR uid = '.$uid.' AND time >= '.$return_time.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) ORDER BY local_chat_id'.$last_message.';';
					}
				}
			}
		}

		return $query;
	}
	////////////////////////////////////////////////////////////////////////////////////////////

	/// static members ///
	public static function create (string $title, array $members, Permissions $permissions): bool
	{}

	// get chats list
	public static function getList (int $offset, int $count): array
	{}
}

/**
 * Permissions class
 * Represents the chat permissions
*/
class Permissions
{
	public function __construct (Chat $chat)
	{}

	public function getValue (string $name): int
	{}

	public function setValue (string $name, int $newValue): bool
	{}
}

/**
 * Message class
 * Represents the message object
*/
class Message
{
	private $owner   = NULL;
	private $localId = NULL;
	private $text    = NULL;
	private $atts    = NULL;
	private $fwd     = NULL;

	// for service messages
	private $event = NULL;

	private $isValid = false;

	private $currentConnection = NULL;

	public function __construct (Chat $chat, int $localMessageId, bool $ignoreDeletion = false)
	{
		$this->currentConnection = new DataBaseConnection();
		if ($chat->valid())
		{
			$uid = $chat->getUID();

			$res = $this->currentConnection->getPDOObject()->prepare("SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments FROM messages.chat_engine_1 WHERE ".($ignoreDeletion ? "" : "deleted_for_all != 1 AND ")."uid = ? AND local_chat_id = ? ORDER BY local_chat_id DESC LIMIT 1;");
			
			if ($res->execute([strval($uid), strval($localMessageId)]))
			{
				$data = $res->fetch(PDO::FETCH_ASSOC);
				if ($data)
				{
					
				}
			}
		}
	}

	public function getOwner (): Entity
	{}

	public function getText (): string
	{}

	public function getAttachments (): array
	{}

	public function getFWD (): array
	{}

	public function setText (string $text): Message
	{}

	public function setAttachments (array $newAttachments): Message
	{}

	public function setFWD (array $fwd): Message
	{}

	public function apply (): int
	{}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function toArray (): array
	{}
}

?>