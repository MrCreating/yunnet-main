<?php

require_once __DIR__ . '/chat.php';

/**
 * one-by-one chat class
 * Have positive id.
*/

class Dialog extends Chat
{
	private $companion = NULL;

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

	public function getMessages (int $count = 100, int $offset = 0): array
	{
		$result = [];

		if ($this->uid === 0) return $result;

		if ($count < 1) $count = 1;
		if ($count > 1000) $count = 1000;
		if ($offset < 0) $offset = 0;

		$res = $this->currentConnection->prepare('SELECT local_chat_id FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND uid = '.$this->uid.' AND local_chat_id > (SELECT cleared_message_id FROM messages.members_chat_list WHERE user_id = '.intval($_SESSION['user_id']).' AND uid = '.$this->uid.' LIMIT 1) AND (deleted_for NOT LIKE "%'.intval($_SESSION['user_id']).',%" OR deleted_for IS NULL) ORDER BY local_chat_id DESC LIMIT '.$offset.','.$count);

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

	public function getCompanion (): Entity
	{
		return $this->companion;
	}

	public function canWrite (): int
	{
		if ($this->getCompanion()->getId() === intval($_SESSION['user_id'])) return 1;

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
}

?>