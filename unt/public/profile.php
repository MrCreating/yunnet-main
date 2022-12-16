<?php

use unt\objects\Context;
use unt\objects\Post;
use unt\objects\Request;

$user = \unt\objects\Entity::findByScreenName(substr(REQUESTED_PAGE, 1));

if ($user->getScreenName() && (substr(REQUESTED_PAGE, 0, 2) == 'id' || substr(REQUESTED_PAGE, 0, 3) == 'bot')) {
	die(header("Location: /".$user->getScreenName()));
}

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	$can_access_closed = $user->getType() === \unt\objects\User::ENTITY_TYPE ? $user->canAccessClosed() : true;
	$in_blacklist      = $user->getType() === \unt\objects\User::ENTITY_TYPE && $user->inBlacklist();

	switch ($action)
	{
		case 'get_posts':
			if (Context::get()->isLogged() && (!$can_access_closed || $in_blacklist))
				die(json_encode(array('error' => 1)));

			$result = [];

			$posts = Post::getList($user->getId(), intval(Request::get()->data['offset'])*20, 20);
			foreach ($posts as $index => $post) {
				$result[] = $post->toArray();
			}

			die(json_encode($result));
		break;

		default:
		break;
	}

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));


	switch ($action)
	{
		case 'set_new_status':
			if (Context::get()->getCurrentUser() && ($user->getId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => intval(Context::get()->getCurrentUser()->edit()->setStatus(Request::get()->data['new_status'])))));
		break;

		case 'add':
			if ($in_blacklist) die(json_encode(array('error' => 1)));

			$result = create_friendship($connection, (Context::get()->getCurrentUser() !== NULL ? Context::get()->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'block':
			$result = block_user($connection, (Context::get()->getCurrentUser() !== NULL ? Context::get()->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'delete':
			$result = delete_friendship($connection, (Context::get()->getCurrentUser() !== NULL ? Context::get()->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'toggle_send_access':
            die(json_encode(array('error' => 1)));
			break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>