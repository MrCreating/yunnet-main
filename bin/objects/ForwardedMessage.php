<?php

/**
 * This class is not extended by Message
 * because this is a different functionality.
*/

class ForwardedMessage
{
	private bool $isValid;

	private $currentConnection;

	private string $text;
	private int    $time;
	private int    $owner_id;
	private int    $id;

	private $fwd;
	private $attachments;

	public function __construct (int $uid, int $messageId)
	{
		$this->isValid           = false;
		$this->currentConnection = DataBaseManager::getConnection();

		$res = $this->currentConnection->prepare("SELECT uid, local_chat_id, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments FROM messages.chat_engine_1 WHERE uid = ? AND local_chat_id = ? LIMIT 1");

		if ($res->execute([$uid, $messageId]))	
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->isValid   = true;
				$this->id        = $messageId;

				$this->text          = strval($data['text']);
				$this->time          = intval($data['time']);
				$this->owner_id      = intval($data['owner_id']);
				$this->attachments   = (new AttachmentsParser())->getObjects($data['reply']);
				$this->fwd           = ForwardedMessage::getList($data['attachments']);
			}
		}
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getText (): string
	{
		return $this->text;
	}

	public function getTime (): int
	{
		return $this->time;
	}

	public function getAttachments (): array
	{
		return $this->attachments;
	}

	public function getForwarded (): array
	{
		return $this->fwd;
	}

	public function getId (): int
	{
		return $this->id;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function toArray (): array
	{
		$result = [
			'id'          => $this->getId(),
			'time'        => $this->getTime(),
			'text'        => $this->getText(),
			'from_id'     => $this->getOwnerId(),
			'fwd'         => array_map(function ($fwd)        { return $fwd->toArray(); },        $this->getForwarded()),
			'attachments' => array_map(function ($attachment) { return $attachment->toArray(); }, $this->getAttachments())
		];

		return $result;
	}

	//////////////////////////////////////////////////
	public static function getList (?string $fwdCredentials): array
	{
		$result = [];

		if (!$fwdCredentials) return $result;

		$parseData = explode('_', $fwdCredentials, 2);

		$chatUID    = intval($parseData[1]);
		$messageIds = array_map(function ($messageId) { return intval($messageId); }, explode(',', $parseData[0]));

		if (count($messageIds) === 0) return $result;

		$resulted_message_ids = [];
		foreach ($messageIds as $index => $msgId) {
			if ($index > 1000) break;

			if (!in_array($msgId, $resulted_message_ids))
			{
				$resulted_message_ids[] = $msgId;

				$message = new ForwardedMessage($chatUID, $msgId);

				if ($message->valid())
					$result[] = $message;
			}
		}

		return $result;
	}
}

?>