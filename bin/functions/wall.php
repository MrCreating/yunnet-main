<?php

/**
 * functions with user's wall.
*/


/**
 * Getting posts for user
*/
function get_posts ($connection, $user_id, $my_id = 0, $count = 50, $only_my_posts = false, $offset = 0)
{
	if (!class_exists('Post'))
		require __DIR__ . '/../objects/post.php';

	if (intval($count) > 100 || intval($count) < 1)
		return false;

	$result = [];

	$pinned_post = [];
	if (!$only_my_posts && $offset === 0)
	{
		$res = $connection->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? AND owner_id = ? AND is_deleted = 0 AND is_pinned = 1 LIMIT 1;');
		$res->execute([intval($user_id), intval($user_id)]);
		$data = $res->fetch(PDO::FETCH_ASSOC);

		if ($data)
			$pinned_post[] = $data;
	}

	$res = $connection->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? '.($only_my_posts ? 'AND owner_id = '.intval($user_id).' ' : '').'AND is_deleted = 0 AND is_pinned = 0 ORDER BY time DESC LIMIT '.intval($offset).','.intval($count).';');
	$res->execute([intval($user_id)]);

	$posts = array_merge($pinned_post, $res->fetchAll(PDO::FETCH_ASSOC));
	foreach ($posts as $index => $post) {
		$post = new Post(intval($user_id), intval($post['local_id']));
		if ($post->valid())
		{
			$result[] = $post;
		}
	}

	return $result;
}

// get news of user
function get_news ($connection, $user_id)
{
	if (!function_exists('get_friends_list'))
		require __DIR__ . "/users.php";

	$result = [];

	$friends_list = array_merge([$user_id], get_friends_list($connection, $user_id, null, 0));
	foreach ($friends_list as $index => $friend_id) {
		
		$res = $connection->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? AND owner_id = ? AND is_deleted = 0 ORDER BY time DESC LIMIT 5;');
		if ($res->execute([intval($friend_id), intval($friend_id)]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);
			foreach ($data as $index => $post) {
				$local_id = intval($post['local_id']);

				$post = get_post_by_id($connection, $friend_id, $local_id, $user_id);
				if ($post)
					$result[] = $post->toArray();
			}
		}

	}

	usort($result, function ($a, $b) {
		return $a['time'] - $b['time'];
	});
	return array_reverse($result);
}

/**
 * get wall by credentials
 * $wall_id = wall id where post may be
 * $post_id = post id.
 */
function get_post_by_id ($connection, $wall_id, $post_id, $user_id = 0)
{
	if (!class_exists('Post'))
		require __DIR__ . '/../objects/post.php';

	$post = new Post(intval($wall_id), intval($post_id));
	if ($post->valid())
	{
		return $post;
	}

	return false;
}

// get comments by credentials
function get_comments ($connection, $attachment, $count = 50, $offset = 0)
{
	$result = [];
	if (intval($count) <= 0 || intval($count) > 100)
		return $result;

	if (substr($attachment, 0, 4) === "wall")
	{
		$post = (new AttachmentsParser())->getObject($attachment);
		if (!$post)
			return $result;

		return $post->getComments($count, $offset);
	}

	return $result;
}

/**
 * Creates a comment to the post
 * @return new comment id - for replying etc.
 *
 * Parameters:
 * @param $wall_id - wall id.
 * @param $post_id - post id.
 * @param $text - text of the comment
 * @param $attachments - attachments of the text
*/
function create_comment ($connection, $owner_id, $wall_id, $post_id, $text = '', $attachments = '')
{
	// if user can not comment - show it.
	if (!can_comment($connection, $owner_id, $wall_id)) return false;

	// connecting modules
	if (!class_exists('AttachmentsParser'))
		require __DIR__ . '/../objects/attachments.php';

	$attachments_string = [];
	$objects = (new AttachmentsParser())->getObjects($attachments);
	foreach ($objects as $index => $attachment) 
	{
		$attachments_string[] = $attachment->getCredentials();
	}

	// empty post is not allowed
	if (is_empty($text) && count($attachments_string) <= 0) return false;

	// too long text is not allowed
	if (strlen($text) > 64000) return false;

	// attachments
	$attachments = implode(',', $attachments_string);

	/**
	 * Now getting the local id and increment it.
	*/
	$dest_attachm = "wall" . $wall_id . "_" . $post_id;
	$res = $connection->prepare("SELECT COUNT(DISTINCT local_id) FROM wall.comments WHERE attachment = :attachment;");
	$res->bindParam(":attachment", $dest_attachm, PDO::PARAM_STR);
	if ($res->execute())
	{
		$new_local_id = intval($res->fetch(PDO::FETCH_ASSOC)['COUNT(DISTINCT local_id)']) + 1;
		$time         = time();

		// creating new post.
		$res = $connection->prepare("INSERT INTO wall.comments (owner_id, local_id, text, time, attachments, attachment) VALUES (:owner_id, :local_id, :text, :time, :attachments, :attachment);");

		// binding post data.
		$res->bindParam(":owner_id",    $owner_id,     PDO::PARAM_INT);
		$res->bindParam(":local_id",    $new_local_id, PDO::PARAM_INT);
		$res->bindParam(":text",        $text,         PDO::PARAM_STR);
		$res->bindParam(":time",        $time,         PDO::PARAM_INT);
		$res->bindParam(":attachments", $attachments,  PDO::PARAM_STR);
		$res->bindParam(":attachment",  $dest_attachm, PDO::PARAM_STR);
		if ($res->execute())
		{
			return get_comment_by_id($connection, $dest_attachm, $owner_id, $new_local_id);
		}
	}

	// another error
	return false;
}

/**
 * Gets comment by credentials
 * @return array with data or false if error
 *
 * Parameters:
 * @param $wall_id - wall id where comment has been located
 * @param $post_id - post id where comment has been located
 * @param $local_id - local id of comment
*/
function get_comment_by_id ($connection, $attachment, $owner_id, $local_id)
{
	// connecting modules
	if (!class_exists('Comment'))
		require __DIR__ . '/../objects/comment.php';
	if (!class_exists('AttachmentsParser'))
		require __DIR__ . '/../objects/attachments.php';

	if (substr($attachment, 0, 4) === "wall")
	{
		$post = (new AttachmentsParser())->getObject($attachment);
		if (!$post)
			return $result;

		$comment = new Comment($post, $owner_id, $local_id);

		if (!$comment->valid())
			return false;

		return $comment;
	}

	// errors?
	return false;
}

/**
 * Checks the psot existance
 *
 * Parameters:
 * @param:$wall_id - integer of wall post destination
 * @param:$post_id - post identifier on wall
 *
 * @return: Boolean - true if post exists
*/
function post_exists ($connection, $wall_id, $post_id) 
{
	$res = $connection->prepare("SELECT owner_id FROM wall.posts WHERE local_id = ? AND to_id = ? AND is_deleted = 0 LIMIT 1;");

	$int_wall_id = intval($wall_id);
	$int_post_id = intval($post_id);

	if ($res->execute([$int_post_id, $int_wall_id]))
	{
		$data = intval($res->fetch(PDO::FETCH_ASSOC)["owner_id"]);

		if (!$data) return false;

		return true;
	}

	return false;
}

// likes selected post
function like_post ($connection, $wall_id, $post_id, $user_id, $post_owner_id)
{
	if (!function_exists('create_notification'))
		require __DIR__ . "/notifications.php";

	if (!post_exists($connection, $wall_id, $post_id)) return false;

	$credentials = "wall" . $wall_id . "_" . $post_id;

	$res = $connection->prepare("SELECT is_liked FROM users.likes WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");

	$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
	$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
	if ($res->execute())
	{
		$data = $res->fetch(PDO::FETCH_ASSOC);
		if (!$data)
		{
			$res = $connection->prepare("INSERT INTO users.likes (user_id, is_liked, attachment) VALUES (:user_id, 1, :attachment);");
			$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
			$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
			if ($res->execute())
			{
				if ($wall_id !== $user_id)
					create_notification($connection, $post_owner_id, "post_like", [
						'user_id' => intval($user_id),
						'data'    => [
							'wall_id' => intval($wall_id),
							'post_id' => intval($post_id)
						]
					]);
				$state = 1;
			}
		}

		$is_liked = intval($data["is_liked"]);
		if ($is_liked)
		{
			$res = $connection->prepare("UPDATE users.likes SET is_liked = 0 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
			$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
			$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
			if ($res->execute())
			{
				$state = 0;
			}
		} else if (!$is_liked && $data)
		{
			$res = $connection->prepare("UPDATE users.likes SET is_liked = 1 WHERE attachment = :attachment AND user_id = :user_id LIMIT 1;");
			$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
			$res->bindParam(":user_id",    $user_id,     PDO::PARAM_INT);
			if ($res->execute())
			{
				$state = 1;
			}
		}
	}

	if ($state !== NULL)
	{
		$res = $connection->prepare("SELECT COUNT(DISTINCT user_id) FROM users.likes WHERE attachment = :attachment AND is_liked = 1;");
		$res->bindParam(":attachment", $credentials, PDO::PARAM_STR);
		$res->execute();
		$likes_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT user_id)"]);

		return [
			'state' => $state,
			'count' => $likes_count
		];
	}

	return false;
}

/**
 * Pin or unpin the post.
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - current user id.
 * @param $wall_id - user id (owner of wall)
 * @param $post_id - post in on the wall
*/
function pin_post ($connection, $user_id, $wall_id, $post_id)
{
	if (!post_exists($connection, $wall_id, $post_id)) return false;
	
	// we can pin only my posts
	if (intval($wall_id) !== intval($user_id)) return false;

	// getting the requested post
	$post = get_post_by_id($connection, $wall_id, $post_id, $user_id);

	// non-existing posts are not allowed
	if (!$post->valid()) return false;

	// only on my wall posts can pin/
	if ($post->getWallId() !== intval($user_id)) return false;

	// only my posts can be pin
	if ($post->getOwnerId() !== intval($user_id)) return false;

	// unpin all user's posts
	if ($connection->prepare("UPDATE wall.posts SET is_pinned = 0 WHERE to_id = ?;")->execute([intval($wall_id)]))
	{
		if (!$post->isPinned())
		{
			return $connection->prepare("UPDATE wall.posts SET is_pinned = 1 WHERE to_id = ? AND local_id = ? LIMIT 1;")->execute([intval($wall_id), intval($post_id)]);
		}

		return -1;
	}

	// error?
	return false;
}

/**
 * Deletes a post.
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - current user id.
 * @param $wall_id - user id (owner of wall)
 * @param $post_id - post in on the wall
*/
function delete_post ($connection, $user_id, $wall_id, $post_id)
{
	/**
	 * We can delete all posts on my wall or all my posts from another wall.
	*/

	// getting the requested post
	$post = get_post_by_id($connection, $wall_id, $post_id, $user_id);

	// non-existing posts are not allowed
	if (!$post->valid()) return false;

	// if not my wall - starts couple of checks
	if (intval($wall_id) !== intval($user_id))
	{
		// can delete only my posts from another wall.
		if ($post->getOwnerId() !== intval($user_id)) return false;
	}

	// deleting the post
	return $connection->prepare("UPDATE wall.posts SET is_deleted = 1 WHERE to_id = ? AND local_id = ? LIMIT 1;")->execute([intval($wall_id), intval($post_id)]);
}

/**
 * Checks the posts write access
 * @return true if you can or false on error
 * 
 * Parameters:
 * @param $user_id - current user id
 * @param $check_id - who check to permission?
*/
function can_write_posts ($connection, $user_id, $check_id)
{
	// current user always can write to itself
	if (intval($user_id) === intval($check_id)) return true;

	// connecting modules
	if (!class_exists('Entity'))
		require __DIR__ . '/../objects/entities.php';

	$object = intval($check_id) > 0 ? new User(intval($check_id)) : new Bot(intval($check_id)*-1);

	// only exists
	if (!$object->valid()) return false;

	$can_write_posts = $object->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_write_on_wall');

	// all users can write
	if ($can_write_posts === 0) return true;

	/**
	 * Here we will to check user friendship.
	 * Connecting users module
	*/
	if (!function_exists('is_friends'))
		require __DIR__ . '/users.php';

	// checking if only friends level set.
	if ($object->getType() === "user" && $can_write_posts === 1 && is_friends($connection, $check_id, $user_id)) return true;

	// only owners can write on bot's wall
	if ($object->getType() === "bot" && $can_write_posts === 2 && intval($user_id) === $object->getOwnerId()) return true;

	// another errors is a false for safety
	return false;
}

/**
 * Creates an a post
 * @return post array if ok or false if error
 *
 * Parameters:
 * @param $owner_id - new owner id of the post
 * @param $wall_id - wall id for new post
 * @param $text (max 128000 symbols)
 * @param $attachments - attachments for the post
*/
function create_post ($connection, $owner_id, $wall_id, $text = '', $attachments = '', $event = '')
{
	// connecting modules
	if (!class_exists('AttachmentsParser'))
		require __DIR__ . '/../objects/attachments.php';
	if (!class_exists('Poll'))
		require __DIR__ . '/../objects/poll.php';
	
	if (!is_empty($event))
	{
		$allowed_events = ['updated_photo'];

		if (!in_array($event, $allowed_events)) $event = '';
	}

	$attachments_string = [];
	$objects = (new AttachmentsParser())->getObjects($attachments);
	foreach ($objects as $index => $attachment) 
	{
		$attachments_string[] = $attachment->getCredentials();
	}

	// empty post is not allowed
	if (is_empty($text) && count($attachments_string) <= 0) return false;

	// too long text is not allowed
	if (strlen($text) > 128000) return false;

	// checking the user existance.
	if (!user_exists($connection, intval($wall_id))) return false;

	// attachments
	$attachments = implode(',', $attachments_string);

	/**
	 * Now getting the local id and increment it.
	*/
	$res = $connection->prepare("SELECT COUNT(DISTINCT local_id) FROM wall.posts WHERE to_id = ?;");
	
	if ($res->execute([intval($wall_id)]))
	{
		$time         = time();
		$new_local_id = intval($res->fetch(PDO::FETCH_ASSOC)['COUNT(DISTINCT local_id)']) + 1;
		
		// creating new post.
		$res = $connection->prepare("INSERT INTO wall.posts (owner_id, local_id, text, time, to_id, attachments, event) VALUES (:owner_id, :local_id, :text, :time, :to_id, :attachments, :event);");

		// binding post data.
		$res->bindParam(":owner_id",    $owner_id,     PDO::PARAM_INT);
		$res->bindParam(":local_id",    $new_local_id, PDO::PARAM_INT);
		$res->bindParam(":text",        $text,         PDO::PARAM_STR);
		$res->bindParam(":time",        $time,         PDO::PARAM_INT);
		$res->bindParam(":to_id",       $wall_id,      PDO::PARAM_INT);
		$res->bindParam(":attachments", $attachments,  PDO::PARAM_STR);
		$res->bindParam(":event",       $event,        PDO::PARAM_STR);
		if ($res->execute())
		{
			return $new_local_id;
		}
	}

	// another errors
	return false;
}

/**
 * Edits the post
 * @return true if ok.
 *
 * Parameters:
 * @param $user_id - who edits the post.
 * @param $wall_id - where post locates?
 * @param $post_id - local id of the post on the wall
 * @param $text - text of the post
 * @param $attachments - attachments for new post
*/
function update_post_data ($connection, $user_id, $wall_id, $post_id, $text = '', $attachments = '')
{
	// connecting modules
	if (!class_exists('AttachmentsParser'))
		require __DIR__ . '/../objects/attachments.php';

	$attachments_list = [];
	$objects = (new AttachmentsParser())->getObjects($attachments);
	foreach ($objects as $index => $attachment) 
	{
		$attachments_list[] = $attachment;
	}

	// empty post is not allowed
	if (is_empty($text) && count($attachments_list) <= 0) return false;

	// too long text is not allowed
	if (strlen($text) > 128000) return false;

	// now getting post.
	$post = get_post_by_id($connection, $wall_id, $post_id);

	// non-existing posts is not allowed
	if (!$post->valid()) return false;

	/**
	 * We can edit only own posts on any wall
	*/
	if ($post->getOwnerId() !== intval($user_id)) return false;

	return $post->setText($text)->setAttachmentsList($attachments_list)->apply();
}

/**
 * Checks the post commenting allowance
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - current user id.
 * @param $check_id - check id.
*/
function can_comment ($connection, $user_id, $check_id)
{
	// user_id must be not 0
	if ($user_id === 0) return false;

	// always can comment itself
	if (intval($user_id) === intval($check_id)) return true;

	// getting the settings
	// connecting modules
	if (!class_exists('Entity')) require __DIR__ . '/../objects/entities.php';
	if (!function_exists('is_friends')) require __DIR__ . '/users.php';

	// we always can comment bot posts
	if (intval($check_id) < 0) return true;

	// getting the user
	$user_object = new User(intval($check_id));
	if (!$user_object->valid()) return false;

	$settings    = $user_object->getSettings()->getSettingGroup('privacy')->getGroupValue('can_comment_posts');

	// all can comment posts
	if ($settings === 0) return true;

	// getting the friendship state
	if ($settings === 1 && is_friends($connection, intval($check_id), intval($user_id))) return true;

	// another errors
	return false;
}

/**
 * Deletes an a comment from attachment
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $attachment - attachment string,
 * @param $comment_id - comment id.
*/
function delete_comment ($connection, $attachment, $comment_id)
{
	// preparing deletion
	$res = $connection->prepare("UPDATE wall.comments SET is_deleted = 1 WHERE attachment = :attachment AND local_id = :local_id AND is_deleted = 0 LIMIT 1;");

	// binding params
	$res->bindParam(":attachment", $attachment, PDO::PARAM_STR);
	$res->bindParam(":local_id",   $comment_id, PDO::PARAM_INT);

	// ok!
	return $res->execute();
}

/**
 * Setting new status for user.
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - user which status will change
 * @param $new_status - max 512 length.
*/
function set_user_status ($connection, $user_id, $new_status)
{
	return Context::get()->getCurrentUser()->edit()->setStatus($new_status);
}
?>