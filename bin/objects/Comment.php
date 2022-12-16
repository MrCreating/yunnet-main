<?php

namespace unt\objects;

/**
 * Comments for posts representation
*/

class Comment extends Attachment
{
    ///////////////////////////////////////
    const ATTACHMENT_TYPE = 'comment';
    ///////////////////////////////////////

	private int $owner_id;
	private int $local_id;
	private int $time;

	private string $text;
	private array $attachments = [];

	private ?Attachment $bound_to;

	function __construct (Post $bound_post, int $local_id)
	{
        parent::__construct();

		if (!$bound_post->valid())
			return;

		$attachment = $bound_post->getCredentials();

		$res = $this->currentConnection->prepare('SELECT text, owner_id, time, local_id, attachments FROM wall.comments WHERE attachment = ? AND is_deleted = 0 AND local_id = ? LIMIT 1');

		if ($res->execute([$attachment, $local_id]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->owner_id = intval($data['owner_id']);
				$this->time     = intval($data['time']);
				$this->local_id = intval($data['local_id']);
				$this->text     = strval($data['text']);

				$this->bound_to = $bound_post;

				if ($data['attachments'])
				{
					$this->setAttachments((new \unt\parsers\AttachmentsParser())->getObjects($data["attachments"]));
				}

				$this->isValid = true;
			}
		}
	}

	public function getCreationTime (): int
	{
		return $this->time;
	}

	public function getType (): string
	{
		return self::ATTACHMENT_TYPE;
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
			$comment['attachments'] = array_map(function ($attachment) {
                return $attachment->toArray();
            }, $attachments);
		}

		return $comment;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getId (): int
	{
		return $this->local_id;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function getText (): string
	{
		return $this->text;
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

		return $this->currentConnection
            ->prepare("UPDATE wall.comments SET is_deleted = 1 WHERE attachment = ? AND local_id = ? LIMIT 1")
            ->execute([$this->getBoundAttachment()->getCredentials(), $this->getId()]);
	}

	public function apply (): bool
	{
		return true;
	}

    public function getBoundAttachment (): ?Attachment
	{
		return $this->bound_to;
	}

    public function getLocalId(): int
    {
        return $this->local_id;
    }
}

?>