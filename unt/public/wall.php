<?php

use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Post;
use unt\objects\Request;
use unt\objects\User;

/**
 * Here is the wall post shower.
*/

// fetching data.
$string = explode('_', substr(REQUESTED_PAGE, 5));

$wall_id = intval($string[0]);
$post_id = intval($string[1]);

if (isset(Request::get()->data["action"]))
{
	$entity = $wall_id > 0 ? User::findById($wall_id) : Bot::findById($wall_id);
	if ($entity && $entity->getType() === User::ENTITY_TYPE && ($entity->inBlacklist() || !$entity->canAccessClosed()))
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
			$result = $post->pin();

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

?>