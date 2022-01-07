<?php

require_once __DIR__ . "/../../bin/functions/wall.php";
require_once __DIR__ . "/../../bin/functions/users.php";

/**
 * Here is the wall post shower.
*/

// fetching data.
$string = explode('_', substr(REQUESTED_PAGE, 5));

$wall_id = intval($string[0]);
$post_id = intval($string[1]);

// if wall is valid
if ($wall_id !== 0)
{
	// if current user is not blacklisted on wall owner
	if (!in_blacklist($connection, $wall_id, $context->getCurrentUser()->getId()))
	{	
		// if current user can not see owner_id wall - redirect
		$can_access_closed = can_access_closed($connection, $context->getCurrentUser()->getId(), $wall_id);
		if (!$can_access_closed)
			die(header("Location: /id".$wall_id));

		$found = true;

		// handle actions with post HERE!!!
		$post = get_post_by_id($connection, $wall_id, $post_id, $context->getCurrentUser()->getId());
		if ($post)
		{
			if (isset(Request::get()->data["action"]))
			{
				if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
				
				$action = strtolower(Request::get()->data["action"]);
				switch ($action) {
					case 'like':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
						$result = like_post($connection, $wall_id, $post_id, $context->getCurrentUser()->getId(), $post->getOwnerId());

						if ($result["state"] === 0)
							die(json_encode(array('result'=>0, 'count'=>intval($result["count"]))));
						if ($result["state"] === 1)
							die(json_encode(array('result'=>1, 'count'=>intval($result["count"]))));

						die(json_encode(array('error'=>1)));
					break;
					case 'pin':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
						$result = pin_post($connection, $context->getCurrentUser()->getId(), $wall_id, $post_id);

						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode(array('result'=>intval($result))));
					break;
					case 'delete':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
						$result = delete_post($connection, $context->getCurrentUser()->getId(), $wall_id, $post_id);

						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode(array('result'=>1)));
					break;
					case 'save_post':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));

						$text = strval(Request::get()->data['text']);
						$attachments = strval(Request::get()->data['attachments']);

						$result = update_post_data($connection, $context->getCurrentUser()->getId(), $wall_id, $post_id, $text, $attachments);
						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode(array('result'=>1)));
					break;
					case 'get':
						$post = get_post_by_id($connection, $wall_id, $post_id, $context->getCurrentUser()->getId());
						if (!$post->valid())
							die(json_encode(array('error'=>1)));

						die(json_encode($post->toArray()));
					break;
					case 'get_comments':
						$comments = get_comments($connection, "wall".$wall_id.'_'.$post_id, intval(Request::get()->data['count']), intval(Request::get()->data['offset']));

						$result = [];
						foreach ($comments as $index => $comment) {
							$result[] = $comment->toArray();
						}

						die(json_encode($result));
					break;
					case 'create_comment':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));

						if (!can_comment($connection, $context->getCurrentUser()->getId(), $wall_id))
							die(json_encode(array('error'=>1)));

						$text        = strval(Request::get()->data['text']);
						$attachments = strval(Request::get()->data['attachments']);

						$result = create_comment($connection, $context->getCurrentUser()->getId(), $wall_id, $post_id, $text, $attachments);
						if (!$result)
							die(json_encode(array('error'=>1)));

						die(json_encode($result->toArray()));
					break;
					case 'delete_comment':
						if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));

						$comment = get_comment_by_id($connection, "wall".$wall_id."_".$post_id, intval($context->getCurrentUser()->getId()), intval(Request::get()->data['comment_id']));
						if (!$comment)
							die(json_encode(array('error'=>1)));

						if ($wall_id === $context->getCurrentUser()->getId() || $comment->getOwnerId() === $context->getCurrentUser()->getId())
						{
							die(json_encode(array('success'=>intval(delete_comment($connection, "wall".$wall_id."_".$post_id, intval(Request::get()->data['comment_id']))))));
						}

						die(json_encode(array('error'=>1)));
					break;
					default:
						die(json_encode(array('error'=>1)));
					break;
				}
			}
		} else {
			if (isset(Request::get()->data["action"]))
				die(json_encode(array('error'=>1)));
		}
	}
}
?>