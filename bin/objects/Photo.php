<?php

namespace unt\objects;

/**
 * Photo class
*/

class Photo extends Attachment
{
    ///////////////////////////////////////
    const ATTACHMENT_TYPE = 'photo';
    ///////////////////////////////////////

	private int $owner_id;
	private int $id;
	private string $access_key;

	private int $width;
	private int $height;

	private string $url;
	private string $query;

	private string $path;

	private int $likes = 0;

	private bool $liked = false;

	function __construct (int $owner_id, int $id, string $access_key)
	{
        parent::__construct();

		$this->url = Project::getDevDomain() . '/images/default.png';

		$res = $this->currentConnection->prepare("SELECT path, query, width, height FROM attachments.d_1 WHERE owner_id = ? AND id = ? AND access_key = ? LIMIT 1;");

		if ($res->execute([strval($owner_id), strval($id), strval($access_key)]))
		{
			$attachment = $res->fetch(\PDO::FETCH_ASSOC);
			if ($attachment)
			{
				$this->owner_id   = $owner_id;
				$this->id         = $id;
				$this->access_key = $access_key;

				$this->url        = Project::getAttachmentsDomain() . '/' . $attachment["query"];
				$this->query      = $attachment["query"];

				$this->path       = __DIR__ . "/../" . $attachment["path"];

				$this->width      = intval($attachment["width"]);
				$this->height     = intval($attachment["height"]);

				$this->isValid    = true;

				$credentials_info = $this->getCredentials();
			
				$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1;");
				if ($res->execute([$credentials_info]))
				{
					$this->likes = intval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
				}

				$user_id = intval($_SESSION['user_id']);
				$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1 AND user_id = ? LIMIT 1;");

				if ($res->execute([$credentials_info, $user_id]))
				{
					$this->liked = boolval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
				}
			}
		}
	}

	public function getQuery (): string
	{
		return strval($this->query);
	}

	public function getLikesCount (): int
	{
		return $this->likes;
	}

	public function isLiked (): bool
	{
		return $this->liked;
	}

	public function getLink (): string
	{
		return $this->url;
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getOwnerId() . '_' . $this->getId() . '_' . $this->getAccessKey();
	}

	public function getWidth (): int
	{
		return $this->width;
	}

	public function getHeight (): int
	{
		return $this->height;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getId (): int
	{
		return $this->id;
	}

	public function getType (): string
	{
		return self::ATTACHMENT_TYPE;
	}

	public function getAccessKey (): string
	{
		return $this->access_key;
	}

	public function toArray(): array
	{
		return [
			'type'  => self::ATTACHMENT_TYPE,
			'photo' => [
				'owner_id'   => $this->getOwnerId(),
				'id'         => $this->getId(),
				'access_key' => $this->getAccessKey(),
				'url'        => [
					'main' => $this->getLink()
				],
				"meta"       => [
					'width'  => $this->getWidth(),
					'height' => $this->getHeight(),
					'likes'  => [
						'count'       => $this->getLikesCount(),
						'liked_by_me' => $this->isLiked()
					]
				]
			]
		];
	}

	public function like (): ?array
	{
		if (!$this->valid()) return NULL;

		$user_id = (int) $_SESSION['user_id'];

		$result = [
			'state'     => 0,
			'new_count' => 0
		];

		$credentials = $this->getCredentials();

		$res = $this->currentConnection->prepare("SELECT is_liked FROM users.likes WHERE attachment = ? AND user_id = ? LIMIT 1;");
		if ($res->execute([$credentials, $user_id]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);
			if (!$data)
			{
				$res = $this->currentConnection->prepare("INSERT INTO users.likes (user_id, is_liked, attachment) VALUES (?, 1, ?);");
				if ($res->execute([$credentials, $user_id]))
				{
					if ($this->owner_id !== $user_id)
					{
						Notification::create($this->owner_id, "photo_like", [
							'user_id' => intval($user_id),
							'data'    => $this->toArray()
						]);
					}

					$result['state'] = 1;
				}
			}

			$is_liked = intval($data["is_liked"]);
			if ($is_liked)
			{
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 0 WHERE attachment = ? AND user_id = ? LIMIT 1;");
				if ($res->execute([$credentials, $user_id]))
				{
					$result['state'] = 0;
				}
			} else if ($data)
			{
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 1 WHERE attachment = ? AND user_id = ? LIMIT 1;");
				if ($res->execute([$credentials, $user_id]))
				{
					$result['state'] = 1;
				}
			}

			$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1;");
			if ($res->execute([$credentials]))
			{
				$result['new_count'] = intval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
			}

			return $result;
		}

		return NULL;
	}
}

?>