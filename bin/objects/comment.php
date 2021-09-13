<?php

if (!function_exists('get_database_connection'))
	require __DIR__ . '/../../base_functions.php';
if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/attachment.php';

/**
 * Comment object
*/

class Comment
{
	private $owner_id = 0;
	private $local_id = 0;

	private $isValid = false;

	private $time = 0;

	private $text = '';
	private $attachments = [];

	private $bound_to = NULL;

	private $currentConnection = NULL;
	function __construct (Post $bound_post, int $owner_id, int $local_id)
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;

		if (!$bound_post->valid())
			return;

		$attachment = $bound_post->getCredentials();

		$res = $connection->prepare('SELECT text, owner_id, time, local_id, attachments FROM wall.comments WHERE attachment = :attachment AND is_deleted = 0 AND owner_id = :owner_id AND local_id = :local_id LIMIT 1;');

		$res->bindParam(":attachment", $attachment, PDO::PARAM_STR);
		$res->bindParam(":local_id",   $local_id,   PDO::PARAM_INT);
		$res->bindParam(":owner_id",   $owner_id,   PDO::PARAM_INT);

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

	public function getCredentials (): string
	{
		return 'comment' . $this->getOwnerId() . '_' . $this->getLocalId();
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