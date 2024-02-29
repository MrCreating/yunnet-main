<?php

namespace unt\objects;

use PDO;

/**
 * one-by-one chat class
 * Have positive id.
*/

class Dialog extends Chat
{
	private ?Entity $companion = NULL;

	public function __construct (string $localId)
	{
		parent::__construct($localId);

		$this->type = 'dialog';

		$res = $this->currentConnection->prepare("SELECT user_id FROM messages.members_chat_list WHERE lid = ? AND uid = ? LIMIT 1");
		if ($res->execute([$_SESSION['user_id'], $this->uid]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$user_id = intval($data['user_id']);
				if ($user_id != 0)
				{
					$entity = Entity::findById($user_id);

					if ($entity && $entity->valid())
					{
						$this->companion = $entity;
						$this->isValid   = true;
					}
				}
			} else
			{
				$entity = Entity::findById(substr($localId, 0, 1) === "b" ? (intval(substr($localId, 1, strlen($localId))) * -1) : intval($localId));
				if ($entity)
				{
					$this->isValid   = true;
					$this->companion = $entity;
				}
			}
		}
	}

	public function getCompanion (): ?Entity
	{
		return $this->companion;
	}

	public function canWrite (): int
	{
		if (!Context::get()->isLogged()) return 0;

		if ($this->getCompanion()->getId() === intval($_SESSION['user_id'])) return 1;

		if (Context::get()->getCurrentUser()->getAccountType() > 0) return 1;
		if (Context::get()->getCurrentUser()->getAccessLevel() > 3) return 1;

		if ($this->getCompanion()->getType() === 'user')
		{
			if ($this->getCompanion()->inBlacklist()) return 0;
		}

		$can_write_to_chat = $this->getCompanion()->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_write_messages');

		if ($can_write_to_chat === 0) return 1;
		if ($can_write_to_chat === 2) return 0;

		if ($can_write_to_chat === 1)
		{
			if ($this->getCompanion()->getType() === 'user' && $this->getCompanion()->isFriends()) return 1;
			if ($this->getCompanion()->getType() === 'bot') return 1;
		}

		return 0;
	}

	protected function getMessagesQuery(int $count = 100, int $offset = 0): string
	{
		return 'SELECT local_chat_id FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND uid = '.$this->uid.' AND local_chat_id > (SELECT cleared_message_id FROM messages.members_chat_list WHERE user_id = '.intval($_SESSION['user_id']).' AND uid = '.$this->uid.' LIMIT 1) AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count;
	}

	// creating new chat if not exists
	protected function init(): bool
	{
		if (!$this->uid)
		{
			$current_time = time();
			$this->uid = get_last_uid() + 1;

			if (!$this->uid) return false;

			$companion_id = $this->getCompanion()->getId();

			$this->currentConnection->getClient()->beginTransaction();

			if (!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([intval($_SESSION['user_id']), $companion_id, $this->uid, $current_time]) ||
				!$this->currentConnection->prepare("INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?)")->execute([$companion_id, intval($_SESSION['user_id']), $this->uid, $current_time])
			) {
				$this->currentConnection->getClient()->rollBack();
				return false;
			} else {
				$this->currentConnection->getClient()->commit();
			}
		}

		return true;
	}

	protected function afterSendMessage(Message $message): bool
	{
		parent::afterSendMessage($message);

		$this->currentConnection->prepare("UPDATE messages.members_chat_list SET hidden = 0, is_read = 0, last_time = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0")->execute([time(), $this->uid]);

		if (intval($_SESSION['user_id']) > 0 && $this->getType() === 'dialog' && $this->getCompanion()->getType() === 'bot')
		{
			toggle_send_access($this->currentConnection, intval($_SESSION['user_id']), $this->getCompanionId());
		}

		return $this->read();
	}

	protected function getCompanionId(): int
	{
		return ($this->getCompanion()->getType() === 'bot' ? $this->getCompanion()->getId() * -1 : $this->getCompanion()->getId());
	}
}
