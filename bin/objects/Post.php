<?php

namespace unt\objects;

use unt\parsers\AttachmentsParser;
use unt\platform\DataBaseManager;

/**
 * Post class
*/

class Post extends Attachment
{
    ///////////////////////////////////////
    const ATTACHMENT_TYPE = 'wall';
    ///////////////////////////////////////

	private int $wall_id;
	private int $post_id;
	private int $creation_time;
	private int $owner_id;

	private string $text;

    /**
     * @var array<Attachment>
     */
	private array $attachments = [];

	private int $comments_count = 0;

	private int $likes = 0;

	private bool $liked = false;
	private bool $is_pinned = false;

	private ?array $event = null;

	function __construct (int $wall_id, int $post_id)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare('SELECT time, text, local_id, to_id, owner_id, attachments, is_pinned, event FROM wall.posts WHERE to_id = ? AND local_id = ? AND is_deleted = 0 LIMIT 1;');

		if ($res->execute([$wall_id, $post_id]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->isValid = true;

				$this->wall_id = $wall_id;
				$this->post_id = $post_id;

				// comments count
				$res = $this->currentConnection->prepare('SELECT COUNT(DISTINCT local_id) FROM wall.comments WHERE attachment = ? AND is_deleted = 0;');

				$credentials_data = $this->getCredentials();
				$res->execute([$credentials_data]);

				$this->creation_time = intval($data['time']);
				$this->owner_id      = intval($data['owner_id']);
				$this->text          = iconv('UTF-8', 'UTF-8//IGNORE', strval($data['text']));
				$this->is_pinned     = boolval($data['is_pinned']);

				$this->comments_count = intval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT local_id)"]);
				if (!is_empty($data['event']))
				{
					$this->event = [];

					$this->event['type'] = $data['event'];
				}

				if ($data["attachments"] !== "")
				{
					$objects = (new \unt\parsers\AttachmentsParser())->getObjects($data["attachments"]);

					foreach ($objects as $attachment)
					{
						$this->attachments[] = $attachment;
					}
				}

				$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1;");

				if ($res->execute([$credentials_data]))
				{
					$this->likes = intval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
				}

				if (intval($_SESSION['user_id']))
				{
					$user_id = intval($_SESSION['user_id']);
					$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1 AND user_id = ? LIMIT 1;");
					if ($res->execute([$credentials_data, $user_id]))
					{
						$this->liked = boolval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
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
		return self::ATTACHMENT_TYPE;
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
		if (!$this->valid()) return NULL;

		$state = NULL;

		$res = $this->currentConnection->prepare("SELECT is_liked FROM users.likes WHERE attachment = ? AND user_id = ? LIMIT 1");

		if ($res->execute([$this->getCredentials(), $_SESSION['user_id']]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);

			if (!$data)
			{
				$res = $this->currentConnection->prepare("INSERT INTO users.likes (user_id, is_liked, attachment) VALUES (?, 1, ?);");
                $credentials = $this->getCredentials();

				if ($res->execute([$_SESSION['user_id'], $credentials]))
				{
					if ($this->getWallId() !== $_SESSION['user_id'])
                        Notification::create($this->getOwnerId(), 'post_like', [
                            'user_id' => $_SESSION['user_id'],
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
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 0 WHERE attachment = ? AND user_id = ? LIMIT 1;");
				if ($res->execute([$this->getCredentials(), $_SESSION['user_id']]))
				{
					$state = 0;
				}
			} else if ($data)
			{
				$res = $this->currentConnection->prepare("UPDATE users.likes SET is_liked = 1 WHERE attachment = ? AND user_id = ? LIMIT 1;");
				if ($res->execute([$this->getCredentials(), $_SESSION['user_id']]))
				{
					$state = 1;
				}
			}
		}

		if ($state !== NULL)
		{
			$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = ? AND is_liked = 1;");

            $likes_count = 0;
            if ($res->execute([$this->getCredentials()]))
			{
				$likes_count = intval($res->fetch(\PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);
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

		$this->currentConnection->prepare("UPDATE wall.posts SET is_pinned = 0 WHERE to_id = ?")->execute([$this->getWallId()]);

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

		if ($user_id === $check_id) return true;

		if ($check_id < 0) return true;

		$user_object = $check_id > 0 ? User::findById($check_id) : Bot::findById($check_id);
		if (!$user_object || $user_object->isBanned()) return false;
		if ($user_object->getType() === User::ENTITY_TYPE && $user_object->inBlacklist()) return false;

		$settings = $user_object->getSettings()->getSettingsGroup(Settings::PRIVACY_GROUP)->getGroupValue('can_comment_posts');

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
        if (strlen($this->getText()) > 128000) return false;
        if (is_empty($this->getText()) && count($this->getAttachmentsList()) === 0) return false;

        $res = $this->currentConnection->prepare("UPDATE wall.posts SET text = ?, attachments = ? WHERE to_id = ? AND local_id = ? LIMIT 1;");

        $attachments_string = implode(',', array_map(function ($attachment) {
            return $attachment->getCredentials();
        }, $this->getAttachmentsList()));

		return $res->execute([$this->getText(), $attachments_string, $this->getWallId(), $this->getPostId()]);
	}

	public function getComments (int $count = 50, int $offset = 0): array
	{
		$result = [];

		if ($this->valid())
		{
			$attachment = $this->getCredentials();

			$res = $this->currentConnection->prepare('SELECT owner_id, local_id FROM wall.comments WHERE attachment = ? AND is_deleted = 0 ORDER BY time LIMIT '. $offset .','. $count);
			if ($res->execute([$attachment]))
			{
				$data_items = $res->fetchAll(\PDO::FETCH_ASSOC);
				if ($data_items)
				{
					foreach ($data_items as $data)
					{
                        $local_id = intval($data['local_id']);

						$comment = new Comment($this, $local_id);
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
			$attachments = $this->getAttachmentsList();

            $result['attachments'] = array_map(function ($attachment) {
                return $attachment->toArray();
            }, $attachments);
		}

		return $result;
	}

	public function getCommentById (int $comment_id): ?Comment
	{
		$comment = new Comment($this, $comment_id);

		if (!$comment->valid()) return NULL;

		return $comment;
	}

	public function createComment (string $text = '', string $attachments = ''): ?Comment
	{
		if (!$this->canComment()) return NULL;

		$text = trim($text);

		$attachments_string = [];
		$objects = (new AttachmentsParser())->getObjects($attachments);
		foreach ($objects as $attachment)
		{
			$attachments_string[] = $attachment->getCredentials();
		}

		if (is_empty($text) && count($attachments_string) <= 0) return NULL;
		if (strlen($text) > 64000) return NULL;

		$attachments  = implode(',', $attachments_string);
		$dest_attachm = $this->getCredentials();

		$res = $this->currentConnection->prepare("SELECT COUNT(DISTINCT local_id) FROM wall.comments WHERE attachment = ?;");
		if ($res->execute([$dest_attachm]))
		{
			$new_local_id = intval($res->fetch(\PDO::FETCH_ASSOC)['COUNT(DISTINCT local_id)']) + 1;

			$res = $this->currentConnection->prepare("INSERT INTO wall.comments (owner_id, local_id, text, time, attachments, attachment) VALUES (?, ?, ?, ?, ?, ?);");

			if ($res->execute([$_SESSION['user_id'], $new_local_id, $text, time(), $attachments, $dest_attachm]))
			{
				$comment = new Comment($this, $new_local_id);
				if ($comment->valid())
					return $comment;
			}
		}

		return NULL;
	}

	////////////////////////////////////////
    const EVENT_PHOTO_UPDATED = 'updated_photo';

	public static function findById (int $wall_id, int $post_id): ?Post
	{
		$post = new static($wall_id, $post_id);

		if (!$post->valid()) return NULL;

		return $post;
	}

	public static function getList (int $wall_id, int $offset = 0, int $count = 50, bool $only_my_posts = false): array
	{
		$count = ($count <= 0 ? 1 : $count) > 1000 ? 1000 : $count;
		$offset = max($offset, 0);

		$result      = [];
		$pinned_post = [];

		if (!$only_my_posts && $offset === 0)
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? AND owner_id = ? AND is_deleted = 0 AND is_pinned = 1 LIMIT 1');
			if ($res->execute([$wall_id, $wall_id]))
			{
				$data = $res->fetch(\PDO::FETCH_ASSOC);

				if ($data)
					$pinned_post[] = $data;
			}
		}

		$res = \unt\platform\DataBaseManager::getConnection()->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? '.($only_my_posts ? 'AND owner_id = '.intval($wall_id).' ' : '').'AND is_deleted = 0 AND is_pinned = 0 ORDER BY time DESC LIMIT '.intval($offset).','. $count .';');

		if ($res->execute([$wall_id]))
		{
			$posts = array_merge($pinned_post, $res->fetchAll(\PDO::FETCH_ASSOC));
			foreach ($posts as $post) {
				$post = new static($wall_id, intval($post['local_id']));
				if ($post->valid())
				{
					$result[] = $post;
				}
			}
		}

		return $result;
	}

    /**
     * Создаёт запись на стене пользователя от имени текущего юзера
     * @param int $wall_id - айди пользователя, на чьей стене создать пост
     * @param string $text
     * @param array<Attachment> $attachments
     * @param ?string $event
     */
    public static function create (int $wall_id, string $text = '', array $attachments = [], ?string $event = NULL): ?Post
    {
        if ($event && $event !== self::EVENT_PHOTO_UPDATED) $event = NULL;

        $attachments_string = '';
        foreach ($attachments as $attachment)
        {
            $attachments_string .= $attachment->getCredentials();
        }

        // empty post is not allowed
        if (is_empty($text) && count($attachments) <= 0) return NULL;

        // too long text is not allowed
        if (strlen($text) > 128000) return NULL;

        // user exists
        $entity = Entity::findById($wall_id);
        if ($entity == NULL) return NULL;

        if (!$entity->canWritePosts()) return NULL;

        $res = DataBaseManager::getConnection()->prepare("SELECT COUNT(DISTINCT local_id) AS last_wall_post_id FROM wall.posts WHERE to_id = ?;");
        if ($res->execute([$wall_id]))
        {
            $time         = time();
            $owner_id     = $_SESSION['user_id'];
            $new_local_id = intval($res->fetch(\PDO::FETCH_ASSOC)['last_wall_post_id']) + 1;
            $event        = (string) $event;

            // creating new post.
            $res = DataBaseManager::getConnection()->prepare("INSERT INTO wall.posts (owner_id, local_id, text, time, to_id, attachments, event) VALUES (:owner_id, :local_id, :text, :time, :to_id, :attachments, :event);");

            // binding post data.
            $res->bindParam(":owner_id",    $owner_id,     \PDO::PARAM_INT);
            $res->bindParam(":local_id",    $new_local_id, \PDO::PARAM_INT);
            $res->bindParam(":text",        $text,         \PDO::PARAM_STR);
            $res->bindParam(":time",        $time,         \PDO::PARAM_INT);
            $res->bindParam(":to_id",       $wall_id,      \PDO::PARAM_INT);
            $res->bindParam(":attachments",
                                                $attachments_string,  \PDO::PARAM_STR);
            $res->bindParam(":event",       $event,        \PDO::PARAM_STR);

            if ($res->execute())
            {
                return Post::findById($wall_id, $new_local_id);
            }
        }

        return NULL;
    }

    public function delete (): bool
    {
        if (DataBaseManager::getConnection()->prepare('UPDATE wall.posts SET is_deleted = 1 WHERE to_id = ? AND local_id = ? LIMIT 1')->execute([$this->getWallId(), $this->getPostId()]))
        {
            $this->isValid = false;
            return true;
        }

        return false;
    }
}

?>