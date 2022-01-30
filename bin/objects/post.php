<?php

require_once __DIR__ . '/attachment.php';
require_once __DIR__ . '/../functions/notifications.php';

/**
 * Post class
*/

class Post extends Attachment
{
	private $wall_id       = 0;
	private $post_id       = 0;
	private $creation_time = 0;
	private $owner_id      = 0;

	private $text    = '';

	private $attachments    = [];
	private $comments_count = 0;

	private $likes = 0;

	private $liked = false;
	private $is_pinned = false;

	private $event = null;

	private $currentConnection = NULL;

	function __construct (int $wall_id, int $post_id)
	{
		$wall_id = intval($wall_id);
		$post_id = intval($post_id);	

		$this->currentConnection = DataBaseManager::getConnection();
		$res = $this->currentConnection->prepare('SELECT time, text, local_id, to_id, owner_id, attachments, is_pinned, event FROM wall.posts WHERE to_id = ? AND local_id = ? AND is_deleted = 0 LIMIT 1;');

		if ($res->execute([intval($wall_id), intval($post_id)]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->isValid = true;

				$this->wall_id = intval($wall_id);
				$this->post_id = intval($post_id);

				// comments count
				$res = $this->currentConnection->prepare('SELECT COUNT(DISTINCT local_id) FROM wall.comments WHERE attachment = :attachment AND is_deleted = 0;');

				$credentials_data = $this->getCredentials();
				$res->bindParam(":attachment", $credentials_data, PDO::PARAM_STR);
				$res->execute();

				$this->creation_time = intval($data['time']);
				$this->owner_id      = intval($data['owner_id']);
				$this->text          = iconv('UTF-8', 'UTF-8//IGNORE', strval($data['text']));
				$this->is_pinned     = boolval($data['is_pinned']);

				$this->comments_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT local_id)"]);
				if (!is_empty($data['event']))
				{
					$this->event = [];

					$this->event['type'] = $data['event'];
				}

				if ($data["attachments"] !== "")
				{
					$objects = (new AttachmentsParser())->getObjects($data["attachments"]);

					foreach ($objects as $index => $attachment) 
					{
						$this->attachments[] = $attachment;
					}
				}

				$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1;");
				$res->bindParam(":attachment", $credentials_data, PDO::PARAM_STR);
				
				if ($res->execute())
				{
					$this->likes = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
				}

				if (intval($_SESSION['user_id']))
				{
					$user_id = intval($_SESSION['user_id']);
					$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1 AND user_id = :user_id LIMIT 1;");

					$res->bindParam(":attachment", $credentials_data, PDO::PARAM_STR);
					$res->bindParam(":user_id",    $user_id,          PDO::PARAM_INT);
					
					if ($res->execute())
					{
						$this->liked = boolval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
					}
				}
			}
		}
	}

	public function getWallId (): int
	{
		return $this->wall_id;
	}

	public function getPostId (): int
	{
		return $this->post_id;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getCreationTime (): int
	{
		return $this->creation_time;
	}

	public function getType (): string
	{
		return "wall";
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getWallId() . '_' . $this->getPostId();
	}

	public function getText (): string
	{
		return $this->text;
	}

	public function setText (string $text): Post
	{
		$this->text = $text;

		return $this;
	}

	public function getAttachmentsList (): array
	{
		return $this->attachments;
	}
	
	public function setAttachmentsList (array $attachments): Post
	{
		$this->attachments = $attachments;

		return $this;
	}

	public function getCommentsCount (): int
	{
		return $this->comments_count;
	}

	public function getLikesCount (): int
	{
		return $this->likes;
	}

	public function liked (): bool
	{
		return $this->liked;
	}

	public function like ()
	{
		if (!$this->valid()) return false;

		$state = NULL;

		$res = $this->currentConnection->prepare("SELECT is_liked FROM users.likes WHERE attachment = :attachment AND user_id = :user_id LIMIT 1");

		$a = $res->bindParam(":attachment", $this->getCredentials(),      PDO::PARAM_STR);
		$b = $res->bindParam(":user_id",    intval($_SESSION['user_id']), PDO::PARAM_INT);

		if ($res->execute())
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if (!$data)
			{
				$res = $this->currentConnection->prepare("INSERT INTO users.likes (user_id, is_liked, attachment) VALUES (:user_id, 1, :attachment);");
				$res->bindParam(":attachment", $this->getCredentials(),      PDO::PARAM_STR);
				$res->bindParam(":user_id",    intval($_SESSION['user_id']), PDO::PARAM_INT);
				if ($res->execute())
				{
					if ($this->getWallId() !== intval($_SESSION['user_id']))
						create_notification($connection, $post_owner_id, "post_like", [
							'user_id' => intval($_SESSION['user_id']),
							'data'    => [
								'wall_id' => $this->getWallId(),
								'post_id' => $this->getPostId()
							]
						]);

					$state = 1;
				}
			}

			$is_liked = intval($data["is_liked"]);
			if ($is_liked)
			{
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 0 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
				$res->bindParam(":attachment", $this->getCredentials(),      PDO::PARAM_STR);
				$res->bindParam(":user_id",    intval($_SESSION['user_id']), PDO::PARAM_INT);
				if ($res->execute())
				{
					$state = 0;
				}
			} else if (!$is_liked && $data)
			{
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 1 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
				$res->bindParam(":attachment", $this->getCredentials(),      PDO::PARAM_STR);
				$res->bindParam(":user_id",    intval($_SESSION['user_id']), PDO::PARAM_INT);
				if ($res->execute())
				{
					$state = 1;
				}
			}
		}

		if ($state !== NULL)
		{
			$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1;");
			$res->bindParam(":attachment", $this->getCredentials(),      PDO::PARAM_STR);
			if ($res->execute())
			{
				$likes_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
			}

			return new class($state, $likes_count) {
				public function __construct (int $state, int $count)
				{
					$this->state = $state;
					$this->count = $count;
				} 

				public function getState (): int
				{
					return $this->state;
				}

				public function getLikesCount (): int
				{
					return $this->count;
				}
			};
		}

		return NULL;
	}

	/**
	 * Results is:
	 * 1 - pinned
	 * 0 - without changes
	 * -1 - unpinned
	*/
	public function pin (): int
	{
		if (!$this->valid()) return 0;

		if ($this->getWallId() !== intval($_SESSION['user_id'])) return 0;

		if ($this->getOwnerId() !== intval($_SESSION['user_id'])) return 0;

		$this->currentConnection->uncache('Post_' . $this->getWallId() . '_' . $this->getPostId())->prepare("UPDATE wall.posts SET is_pinned = 0 WHERE to_id = ?")->execute([$this->getWallId()]);

		if (!$this->isPinned())
		{
			return (int) $this->currentConnection->prepare("UPDATE wall.posts SET is_pinned = 1 WHERE to_id = ? AND local_id = ? LIMIT 1")->execute([$this->getWallId(), $this->getPostId()]);
		}

		return -1;
	}

	public function canComment (): bool
	{
		$user_id  = intval($_SESSION['user_id']);
		$check_id = $this->getOwnerId();

		if ($user_id === 0) return false;

		if (intval($user_id) === intval($check_id)) return true;

		if (intval($check_id) < 0) return true;

		$user_object = Entity::findById(intval($check_id));
		if (!$user_object || $user_object->isBanned()) return false;
		if ($user_object->getType() === 'user' && $user_object->inBlacklist()) return false;

		$settings = $user_object->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_comment_posts');

		if ($settings === 0) return true;

		if ($settings === 1 && $user_object->getAccountType() === 'user' && $user_object->isFriends()) return true;
		if (($settings === 1 || $settings === 2) && $user_object->getAccountType() === 'bot' && $user_object->getOwnerId() === $user_id) return true;

		return false;
	}

	public function isPinned (): bool
	{
		return $this->is_pinned;
	}

	public function apply (): bool
	{
		$res = $this->currentConnection->uncache('Post_' . $this->getWallId() . '_' . $this->getPostId())->prepare("UPDATE wall.posts SET text = :text WHERE to_id = :to_id AND local_id = :local_id LIMIT 1;");

		$res->bindParam(":text",     $this->getText(),   PDO::PARAM_STR);
		$res->bindParam(":to_id",    $this->getWallId(), PDO::PARAM_INT);
		$res->bindParam(":local_id", $this->getPostId(), PDO::PARAM_INT);

		if ($res->execute())
		{
			$attachments = '';

			$attachments_list = $this->getAttachmentsList();
			foreach ($attachments_list as $index => $attachment) {
				$attachments .= $attachment->getCredentials();

				if ($index !== (count($attachments_list) - 1))
					$attachments .= ',';
			}

			$res = $this->currentConnection->uncache('Post_' . $this->getWallId() . '_' . $this->getPostId())->prepare("UPDATE wall.posts SET attachments = :attachments WHERE to_id = :to_id AND local_id = :local_id LIMIT 1;");

			$res->bindParam(":attachments", $attachments,       PDO::PARAM_STR);
			$res->bindParam(":to_id",       $this->getWallId(), PDO::PARAM_INT);
			$res->bindParam(":local_id",    $this->getPostId(), PDO::PARAM_INT);

			return $res->execute();
		}

		return false;
	}

	public function getComments ($count = 50, $offset = 0): array
	{
		$result = [];

		if ($this->valid())
		{
			$attachment = $this->getCredentials();

			$res = $this->currentConnection->prepare('SELECT owner_id, local_id FROM wall.comments WHERE attachment = :attachment AND is_deleted = 0 ORDER BY time LIMIT '.intval($offset).','.intval($count).';');

			$res->bindParam(":attachment", $attachment, PDO::PARAM_STR);
			if ($res->execute())
			{
				$data_items = $res->fetchAll(PDO::FETCH_ASSOC);
				if ($data_items)
				{
					foreach ($data_items as $index => $data)
					{
						$owner_id = intval($data['owner_id']);
						$local_id = intval($data['local_id']);

						$comment = new Comment($this, $owner_id, $local_id);
						if ($comment->valid())
							$result[] = $comment;
					}
				}
			}
		}

		return $result;
	}

	public function toArray (): array
	{
		$result = [
			'id'        => $this->getPostId(),
			'time'      => $this->getCreationTime(),
			'owner_id'  => $this->getOwnerId(),
			'user_id'   => $this->getWallId(),
			'text'      => $this->getText(),
			'is_pinned' => $this->isPinned(),

			'comments'  => [
				'count' => $this->getCommentsCount()
			],

			'fields'    => [
				'can_comment' => $this->canComment()
			],

			'likes'     => $this->getLikesCount(),
			'like_me'   => $this->liked()
		];

		if ($this->event)
		{
			$result['event'] = $this->event;
		}

		if (count($this->getAttachmentsList()) !== 0)
		{
			$result['attachments'] = [];

			$attachments = $this->getAttachmentsList();

			foreach ($attachments as $index => $attachment) {
				$result['attachments'][] = $attachment->toArray();	
			}
		}

		return $result;
	}

	////////////////////////////////////////
	public static function findById (int $wall_id, int $post_id): ?Post
	{
		$post = new Post($wall_id, $post_id);

		if (!$post->valid()) return NULL;

		return $post;
	}

	public static function getList (int $wall_id, int $offset = 0, int $count = 50, bool $only_my_posts = false): array
	{
		$connection = DataBaseManager::getConnection();

		$count = ($count <= 0 ? 1 : $count) > 1000 ? 1000 : $count;
		$offset = $offset <= 0 ? 0 : $offset;

		$result      = [];
		$pinned_post = [];

		if (!$only_my_posts && $offset === 0)
		{
			$res = $connection->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? AND owner_id = ? AND is_deleted = 0 AND is_pinned = 1 LIMIT 1');
			if ($res->execute([intval($wall_id), intval($wall_id)]))
			{
				$data = $res->fetch(PDO::FETCH_ASSOC);

				if ($data)
					$pinned_post[] = $data;
			}
		}

		$res = $connection->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? '.($only_my_posts ? 'AND owner_id = '.intval($wall_id).' ' : '').'AND is_deleted = 0 AND is_pinned = 0 ORDER BY time DESC LIMIT '.intval($offset).','.intval($count).';');

		if ($res->execute([intval($wall_id)]))
		{
			$posts = array_merge($pinned_post, $res->fetchAll(PDO::FETCH_ASSOC));
			foreach ($posts as $index => $post) {
				$post = new Post($wall_id, intval($post['local_id']));
				if ($post->valid())
				{
					$result[] = $post;
				}
			}
		}

		return $result;
	}
}

?>