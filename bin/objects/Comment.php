<?php

/**
 * Comments for posts representation
*/

class Comment extends Attachment
{
	private $owner_id = 0;
	private $local_id = 0;

	private $time = 0;

	private $text = '';
	private $attachments = [];

	private $bound_to = NULL;

	private $currentConnection = NULL;
	function __construct (Post $bound_post, int $local_id)
	{
		$this->currentConnection = DataBaseManager::getConnection();

		if (!$bound_post->valid())
			return;

		$attachment = $bound_post->getCredentials();

		$res = $this->currentConnection/*->cache('Comment_' . $bound_post->getWallId() . '_' . $bound_post->getPostId() . '_' . $local_id)*/->prepare('SELECT text, owner_id, time, local_id, attachments FROM wall.comments WHERE attachment = :attachment AND is_deleted = 0 AND local_id = :local_id LIMIT 1');

		$res->bindParam(":attachment", $attachment, PDO::PARAM_STR);
		$res->bindParam(":local_id",   $local_id,   PDO::PARAM_INT);

		if ($res->execute())
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->owner_id = intval($data['owner_id']);
				$this->time     = intval($data['time']);
				$this->local_id = intval($data['local_id']);
				$this->text     = strval($data['text']);

				$this->bound_to = $bound_post;

				if ($data['attachments'])
				{
					$this->setAttachments((new AttachmentsParser())->getObjects($data["attachments"]));
				}

				$this->isValid = true;
			}
		}
	}

	public function getCreationTime (): int
	{
		return intval($this->time);
	}

	public function getType (): string
	{
		return "comment";
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getOwnerId() . '_' . $this->getLocalId();
	}

	public function toArray (): array
	{
		$comment = [
			'owner_id'   => $this->getOwnerId(),
			'time'       => $this->getCreationTime(),
			'comment_id' => $this->getId(),
			'text'       => $this->getText()
		];

		$attachments = $this->getAttachments();
		if (count($attachments) > 0)
		{
			$comment['attachments'] = [];

			foreach ($attachments as $index => $attachment) 
			{
				$comment['attachments'][] = $attachment->toArray();
			}
		}

		return $comment;
	}

	public function getOwnerId (): int
	{
		return intval($this->owner_id);
	}

	public function getId (): int
	{
		return intval($this->local_id);
	}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	public function getText (): string
	{
		return strval($this->text);
	}

	public function setText (string $new_text): Comment
	{
		$this->text = $new_text;

		return $this;
	}

	public function getAttachments (): array
	{
		return $this->attachments;
	}

	public function setAttachments (array $attachments = []): Comment
	{
		$this->attachments = $attachments;

		return $this;
	}

	public function delete (): bool
	{
		if (!$this->valid()) return false;

		if ($this->getOwnerId() !== intval($_SESSION['user_id']) && $this->getBoundAttachment()->getOwnerId() !== intval($_SESSION['user_id'])) return false;

		$res = $this->currentConnection->uncache('Comment_' . $this->getBoundAttachment()->getWallId() . '_' . $this->getBoundAttachment()->getPostId() . '_' . $this->getId())->prepare("UPDATE wall.comments SET is_deleted = 1 WHERE attachment = :attachment AND local_id = :local_id LIMIT 1");

		// binding params
		$res->bindParam(":attachment", $this->getBoundAttachment()->getCredentials(), PDO::PARAM_STR);
		$res->bindParam(":local_id",   $this->getId(),                                PDO::PARAM_INT);

		// ok!
		return $res->execute();
	}

	public function apply (): bool
	{
		return true;
	}

	function getBoundAttachment ()
	{
		return $this->bound_to;
	}
}

?>