<?php

/**
 * Message class
 * Represents the message object
*/

class Message
{
	private $owner_id    = NULL;
	private $local_id    = NULL;
	private $text        = NULL;
	private $attachments = NULL;
	private $fwd         = NULL;

	// for service messages
	private $event = NULL;

	private $isValid = false;

	private $currentConnection = NULL;

	public function __construct (Chat $chat, int $localMessageId, bool $ignoreDeletion = false)
	{
		$this->currentConnection = DataBaseManager::getConnection();
		if ($chat->valid())
		{
			$uid = $chat->getUID();

			$res = $this->currentConnection->prepare("SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments FROM messages.chat_engine_1 WHERE ".($ignoreDeletion ? "" : "deleted_for_all != 1 AND ")."uid = ? AND local_chat_id = ? ORDER BY local_chat_id DESC LIMIT 1;");
			
			if ($res->execute([strval($uid), strval($localMessageId)]))
			{
				$data = $res->fetch(PDO::FETCH_ASSOC);
				if ($data)
				{
					
				}
			}
		}
	}

	public function getId (): int
	{
		return intval($this->local_id);
	}

	public function getOwnerId (): int
	{
		return intval($this->owner_id);
	}

	public function getText (): string
	{
		return strval($this->text);
	}

	public function getAttachments (): array
	{
		return $this->attachments;
	}

	public function getFWD (): array
	{
		return $this->fwd;
	}

	public function setText (string $text): Message
	{
		return $this;
	}

	public function setAttachments (array $newAttachments): Message
	{
		return $this;
	}

	public function setFWD (array $fwd): Message
	{
		return $this;
	}

	public function apply (): int
	{
		return $this->getId();
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function toArray (): array
	{
		return [];
	}
}

?>