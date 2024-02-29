<?php

namespace unt\objects;

use PDO;
use unt\parsers\AttachmentsParser;
use unt\platform\DataBaseManager;
use unt\platform\EventManager;

require_once __DIR__ . '/../functions/messages.php';

/**
 * Base chat class.
 * Used for dialog and conversations
*/

abstract class Chat extends BaseObject
{
    protected int $uid = 0;
    protected int $peer_id = 0;
    protected int $lastReadMsgId = 0;

    protected bool $isValid = false;
    protected bool $is_read = false;
    protected bool $notifications_enabled = true;

    protected string $type = 'chat';

    protected DataBaseManager $currentConnection;

    abstract public function canWrite(): int;

	abstract protected function getMessagesQuery (int $count = 100, int $offset = 0): string;

	abstract protected function getCompanionId (): int;

    public function __construct(string $localId)
    {
        parent::__construct();

        $this->isValid = false;
        $this->currentConnection = DataBaseManager::getConnection();

        $this->notifications_enabled = true;
        $this->is_read = true;
        $this->type = 'chat';

        $is_bot_dialog = false;
        $is_multi_chat = false;
        $local_chat_id = 0;

        if (substr($localId, 0, 1) === "b") {
            $is_bot_dialog = true;
            $local_chat_id = intval(substr($localId, 1, strlen($localId))) * -1;
        } else if (intval($localId) < 0) {
            $is_multi_chat = true;
            $local_chat_id = intval($localId);
        } else if (intval($localId) > 0) {
            $local_chat_id = intval($localId);
        }

        $this->uid = 0;
        $res = $this->currentConnection->prepare($is_bot_dialog ?
            "SELECT uid, last_read_message_id, notifications, show_pinned_messages FROM messages.members_chat_list WHERE lid = ? AND uid > 0 AND user_id = ? LIMIT 1"
            :
            "SELECT uid, last_read_message_id, notifications, show_pinned_messages FROM messages.members_chat_list WHERE lid = ? AND user_id = ? LIMIT 1"
        );

        if ($res->execute([$local_chat_id, $_SESSION['user_id']])) {
            $result = $res->fetch(PDO::FETCH_ASSOC);

            if (isset($result['uid'])) {
                $this->isValid = true;
                $this->uid = intval($result['uid']);

                $this->peer_id = intval($local_chat_id);
                $this->is_read = intval($result['is_read']);
                $this->lastReadMsgId = intval($result['last_read_message_id']);
                $this->notifications_enabled = intval($result['notifications']);
            } else {
                $this->uid = 0;
            }
        }
    }

	public function getMessages (int $count = 100, int $offset = 0): array
	{
		$result = [];

		if ($this->uid === 0) return $result;

		if ($count < 1) $count = 1;
		if ($count > 1000) $count = 1000;
		if ($offset < 0) $offset = 0;

		$query = $this->getMessagesQuery($count, $offset);

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

    public function getLocalChatId(int $uid): int
    {
        $text_engine_init = curl_init("text_engine");

        $data = json_encode([
            'operation' => 'get_lid',
            'uid'       => $uid
        ]);

        curl_setopt($text_engine_init, CURLOPT_POSTFIELDS,     $data);
        curl_setopt($text_engine_init, CURLOPT_POST,           1);
        curl_setopt($text_engine_init, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));
        curl_setopt($text_engine_init, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($text_engine_init);
        curl_close($text_engine_init);

        return intval($result);
    }

	public function getLastReadMessageId (): int
	{
		return $this->lastReadMsgId;
	}

	public function getUID (): int
	{
		return $this->uid ?: 0;
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
		return $this->isValid;
	}

	public function isNotificationsEnabled (): bool
	{
		return $this->notifications_enabled;
	}

	public function setNotificationsEnabled (): bool
	{
		$this->notifications_enabled = !$this->notifications_enabled;

		return $this->currentConnection->prepare("UPDATE messages.chats SET notifications = ? WHERE uid = ? AND user_id = ? LIMIT 1")->execute([intval($this->notifications_enabled), $this->uid, intval($_SESSION['user_id'])]);
	}

	public function clear (): bool
	{
		$last_message = $this->getLastMessage();
		if ($last_message)
		{
			$last_message_id = $last_message->getId();
			$this->read($last_message_id);

			if ($this->currentConnection->prepare("UPDATE messages.members_chat_list SET last_clear_id = ? WHERE user_id = ? AND uid = ? LIMIT 1")->execute([$last_message_id, intval($_SESSION['user_id']), $this->uid]))
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

				return EventManager::event([intval($_SESSION['user_id'])], $event);
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
		$last_read_message_id = $messageId <= 0 ? $this->getLastMessage()->getId() : $messageId;

		if (
			$this->currentConnection->prepare("
                UPDATE messages.members_chat_list SET last_read_message_id = ? WHERE user_id = ? AND uid = ? LIMIT 1
            ")->execute([$last_read_message_id, intval($_SESSION['user_id']), $this->uid]))
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

			return EventManager::event([intval($_SESSION['user_id'])], $event);
		}

		return false;
	}

	abstract protected function init (): bool;

	protected function afterSendMessage (Message $message): bool
	{
		return true;
	}

	public function sendMessage (string $text = '', string $attachments = '', string $fwd = '', string $payload = ''): int
	{
		if (($validationResult = $this->isMessageDataValid($text, $attachments)) !== 1) {
			return $validationResult;
		}

		$can_write_messages = $this->canWrite();
		if ($can_write_messages === 0) return -1;
		if ($can_write_messages === -1 || $can_write_messages === -2) return -2;

		$attachments_list = (new AttachmentsParser())->getObjects($attachments);
		$done_text    = trim($text);
		$done_atts    = '';

		foreach ($attachments_list as $value) {
			$done_atts .= $value->getCredentials();
		}

		if (!$this->init()) {
			return -10;
		}

		if (($resultSend = $this->saveMessage($done_text, $done_atts)) <= 0) {
			return $resultSend;
		}

		if ($this->emitEventToAllMembers()) {
			$this->afterSendMessage(new Message($this, $resultSend));
		}

		return $resultSend;
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

		if (!$this->init()) {
			return -10;
		}

		if (($resultSend = $this->saveMessage('', '', $event, $new_src, $new_title)) <= 0) {
			return $resultSend;
		}

		if ($this->emitEventToAllMembers()) {
			$this->afterSendMessage(new Message($this, $resultSend));
		}

		return $resultSend;
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

		if ($this->getType() === 'dialog' &&  $this->getCompanion() !== NULL && $this->getCompanion()->getType() === 'bot')
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
			'is_bot_chat'   => intval($this->getType() === 'dialog' && $this->getCompanion() !== NULL && $this->getCompanion()->getType() === 'bot'),
			'data'          => []
		];

		if ($this->getType() === 'dialog' && $this->getCompanion() !== NULL)
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

		$res = DataBaseManager::getConnection()->prepare($query);
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

	public static function findById (string $localId): ?Chat
	{
		$dialog = intval($localId) < 0 ? new Conversation($localId) : new Dialog($localId);

		if ($dialog->valid())
			return $dialog;

		return NULL;
	}

	//////////////////////////////////////////////////////////////
	private function saveMessage(string $done_text, string $done_atts, string $event = NULL, string $new_src = NULL, string $new_title = NULL): int
	{
		$current_time = time();
		$companion_id = $this->getCompanionId();

		$local_message_id = $this->getLocalChatId($this->uid);
		if (!$local_message_id) return -8;

		$params = [
			$this->uid,
			intval($_SESSION['user_id']),
			$local_message_id,
			$done_text,
			'',
			$done_atts,
			$current_time,
			$companion_id
		];

		if ($event === NULL) {
			$query = "INSERT INTO messages.chat_engine_1 (
				uid, owner_id, local_chat_id, text, attachments, reply, time, flags, to_id
			) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)";
		} else {
			$query = "INSERT INTO messages.chat_engine_1 (
				uid, owner_id, local_chat_id, text, attachments, reply, time, flags, to_id, event, new_src, new_title
			) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)";

			$params[] = $event;
			$params[] = $new_src;
			$params[] = $new_title;
		}

		$res = $this->currentConnection->prepare($query);

		if (!$res->execute($params)) return -9;

		return $local_message_id;
	}

	private function emitEventToAllMembers (): int
	{
		// TODO: ПЕРЕПИСАТЬ НА НОВЫЙ ОБРАБОТЧИК СОБЫТИЙ
//		$atts_array = [];
//		foreach ($attachments_list as $index => $attachment)
//		{
//			$atts_array[] = $attachment->toArray();
//		}
//
//		$event = [
//			'event' => 'new_message',
//			'message' => [
//				'from_id'     => intval($_SESSION['user_id']),
//				'id'          => $local_message_id,
//				'type'        => 'message',
//				'text'        => $done_text,
//				'time'        => $current_time,
//				'attachments' => $atts_array,
//				'fwd'         => []
//			],
//			'uid' => $this->uid
//		];
//
//		if (!unt\functions\is_empty($payload) && strlen($payload) < 1024)
//			$event['payload'] = $payload;
//
//		$user_ids  = [];
//		$local_ids = [];
//
//		if ($this->getType() === 'dialog')
//		{
//			if (intval($_SESSION['user_id']) === $this->getCompanion()->getId())
//			{
//				$user_ids  = [intval($_SESSION['user_id'])];
//				$local_ids = [intval($_SESSION['user_id'])];
//			} else
//			{
//				$user_ids  = [intval($_SESSION['user_id']), $companion_id];
//				$local_ids = [$companion_id, intval($_SESSION['user_id'])];
//			}
//
//			if ($this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
//			{
//				if (intval($_SESSION['user_id']) < 0)
//					$event['bot_peer_id'] = intval($_SESSION['user_id']);
//				if ($companion_id < 0)
//					$event['bot_peer_id'] = $companion_id;
//			}
//		} else
//		{
//			$member_ids = $this->getMembers();
//
//			foreach ($member_ids as $index => $user_info) {
//				$user_ids[]  = $user_info->user_id;
//				$local_ids[] = $user_info->local_id;
//			}
//		}
//
//        //EventManager::event($user_ids, $local_ids, $event);

		return 1;
	}

	private function isMessageDataValid (string $text, string $attachments): int
	{
		$attachments_list = (new AttachmentsParser())->getObjects($attachments);

		if (is_empty($text) && count($attachments_list) === 0) return -3;

		if (strlen($text) > 4096) return -3;

		if (count($attachments_list) > 10) return -5;

		return 1;
	}
}

?>
