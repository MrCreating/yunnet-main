<?php

require_once __DIR__ . "/../../bin/objects/Post.php";
require_once __DIR__ . "/../../bin/functions/wall.php";
require_once __DIR__ . "/../../bin/functions/users.php";

/**
 * Here is the wall post shower.
*/

// fetching data.
$string = explode('_', substr(REQUESTED_PAGE, 5));

$wall_id = intval($string[0]);
$post_id = intval($string[1]);

if (isset(Request::get()->data["action"]))
{
	$entity = Entity::findById($wall_id);
	if ($entity && $entity->getType() === 'user' && ($entity->inBlacklist() || !$entity->canAccessClosed()))
		die(json_encode(array('error' => 1)));

	$post = Post::findById($wall_id, $post_id);

	if (!$post)
		die(json_encode(array('error' => 1)));

	$action = strtolower(Request::get()->data["action"]);
	switch ($action) 
	{
		case 'get':
			die(json_encode($post->toArray()));
		break;

		case 'get_comments':
			die(json_encode(array_map(function ($item) {
				return $item->toArray();
			}, $post->getComments())));
		break;
	}

	if (!Context::get()->allowToUseUnt())
		die(json_encode(array('error' => 1)));

	switch ($action) 
	{
		case 'like':
			$result = $post->like();

			if (!$result || ($result->getState() !== 0 && $result->getState() !== 1))
				die(json_encode(array('error' => 1)));

			die(json_encode(array('result' => $result->getState(), 'count' => $result->getLikesCount())));
		break;

		case 'pin':
			$result = $post ? $post->pin() : 0;

			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('result' => intval($result))));
		break;

		case 'create_comment':
			$text        = strval(Request::get()->data['text']);
			$attachments = strval(Request::get()->data['attachments']);

			$result = $post->createComment($text, $attachments);
			if ($result)
				die(json_encode($result->toArray()));
		break;

		case 'delete_comment':
			$comment_id = intval(Request::get()->data['comment_id']);

			$comment = $post->getCommentById($comment_id);
			if ($comment)
			{
				if ($comment->delete())
					die(json_encode(array('success' => 1)));
			}
		break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

// if wall is valid
/*if ($wall_id !== 0)
{
	// if current user is not blacklisted on wall owner
	if (!in_blacklist($connection, $wall_id, Context::get()->getCurrentUser()->getId()))
	{	
		// if current user can not see owner_id wall - redirect
		$can_access_closed = can_access_closed($connection, Context::get()->getCurrentUser()->getId(), $wall_id);
		if (!$can_access_closed)
			die(header("Location: /id".$wall_id));

		// handle actions with post HERE!!!
		$post = Post::findById($wall_id, $post_id);
		if ($post)
		{
			if (isset(Request::get()->data["action"]))
			{
				if (!Context::get()->isLogged()) die(json_encode(array('unauth'=>1)));
				
				$action = strtolower(Request::get()->data["action"]);
				switch ($action) {
					case 'delete':
						if (!Context::get()->isLogged()) die(json_encode(array('unauth'=>1)));
						$result = delete_post($connection, Context::get()->getCurrentUser()->getId(), $wall_id, $post_id);

						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode(array('result'=>1)));
					break;
					case 'save_post':
						if (!Context::get()->isLogged()) die(json_encode(array('unauth'=>1)));

						$text = strval(Request::get()->data['text']);
						$attachments = strval(Request::get()->data['attachments']);

						$result = update_post_data($connection, Context::get()->getCurrentUser()->getId(), $wall_id, $post_id, $text, $attachments);
						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode(array('result'=>1)));
					break;
				}
			}
		}
	}
}*/

?>