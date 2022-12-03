<?php

require_once __DIR__ . '/Attachment.php';

/**
 * Photo class
*/

class Photo extends Attachment
{
	private $owner_id   = 0;
	private $id         = 0;
	private $access_key = "";

	private $width  = 0;
	private $height = 0;

	private $url   = NULL;
	private $query = '';

	private $path  = '';

	private $likes = 0;
	private $liked = 0;

	private $currentConnection = false;

	function __construct ($owner_id, $id, $access_key)
	{
		$this->url = Project::getDevDomain() . '/images/default.png';

		$this->currentConnection = DataBaseManager::getConnection();

		$res = $this->currentConnection/*->cache("Photo_" . $owner_id . "_" . $id . "_" . $access_key)*/->prepare("SELECT path, query, width, height FROM attachments.d_1 WHERE owner_id = ? AND id = ? AND access_key = ? LIMIT 1;");

		if ($res->execute([strval($owner_id), strval($id), strval($access_key)]))
		{
			$attachment = $res->fetch(PDO::FETCH_ASSOC);
			if ($attachment)
			{
				$this->owner_id   = intval($owner_id);
				$this->id         = intval($id);
				$this->access_key = strval($access_key);

				$this->url        = Project::getAttachmentsDomain() . '/' . $attachment["query"];
				$this->query      = $attachment["query"];

				$this->path       = __DIR__."/../".$attachment["path"];
				$this->width      = intval($attachment["width"]);
				$this->height     = intval($attachment["height"]);
				$this->isValid    = true;

				$credentials_info = $this->getCredentials();
			
				$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1;");
				$res->bindParam(":attachment", $credentials_info, PDO::PARAM_STR);		
				if ($res->execute())
				{
					$this->likes = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
				}

				$user_id = intval($_SESSION['user_id']);
				$res = $this->currentConnection/*->cache("Liked_" . $user_id)*/->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1 AND user_id = :user_id LIMIT 1;");

				$res->bindParam(":attachment", $credentials_info, PDO::PARAM_STR);
				$res->bindParam(":user_id",    $user_id,          PDO::PARAM_INT);
				if ($res->execute())
				{
					$this->liked = boolval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
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
		return boolval($this->liked);
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
		return "photo";
	}

	public function getAccessKey (): string
	{
		return $this->access_key;
	}

	public function toArray(): array
	{
		return [
			'type'  => 'photo',
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

	public function like ()
	{
		if (!$this->valid()) return false;

		$user_id    = intval($_SESSION['user_id']);

		if (!function_exists('create_notification'))
			require __DIR__ . '/../functions/notifications.php';

		$result = [
			'state'     => 0,
			'new_count' => 0
		];

		$credentials = $this->getCredentials();

		$res = $this->currentConnection/*->cache("Liked_" . $user_id)*/->prepare("SELECT is_liked FROM users.likes WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
		$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
		$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);

		if ($res->execute())
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if (!$data)
			{
				$res = $this->currentConnection->uncache("Liked_" . $user_id)->prepare("INSERT INTO users.likes (user_id, is_liked, attachment) VALUES (:user_id, 1, :attachment);");
				$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
				$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
				if ($res->execute())
				{
					if ($this->owner_id !== $user_id)
					{
						create_notification($connection, $this->owner_id, "photo_like", [
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
				$res = $this->currentConnection->uncache("Liked_" . $user_id)->prepare("UPDATE users.likes SET is_liked = 0 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
				$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
				$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
				if ($res->execute())
				{
					$result['state'] = 0;
				}
			} else if (!$is_liked && $data)
			{
				$res = $this->currentConnection->uncache("Liked_" . $user_id)->prepare("UPDATE users.likes SET is_liked = 1 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
				$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
				$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
				if ($res->execute())
				{
					$result['state'] = 1;
				}
			}

			$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1;");
			$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
			
			if ($res->execute())
			{
				$result['new_count'] = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
			}

			return $result;
		}

		return false;
	}
}

?>