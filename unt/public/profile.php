<?php

use unt\objects\Context;
use unt\objects\Entity;
use unt\objects\Post;
use unt\objects\Request;
use unt\objects\User;

$user = Entity::findByScreenName(substr(REQUESTED_PAGE, 1));

if ($user && $user->getScreenName() && (substr(REQUESTED_PAGE, 0, 2) == 'id' || substr(REQUESTED_PAGE, 0, 3) == 'bot')) {
	die(header("Location: /".$user->getScreenName()));
}

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);
    if ($action === 'get_page')
    {
        die(\unt\design\Template::get('profile')->show());
    }

    if (!$user)
        die(json_encode(array('error' => 1)));

	$can_access_closed = $user->getType() === User::ENTITY_TYPE ? $user->canAccessClosed() : true;
	$in_blacklist      = $user->getType() === User::ENTITY_TYPE && $user->inBlacklist();

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

        case 'add':
			if ($in_blacklist) die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => (int)$user->addToFriends())));

        case 'block':
			die(json_encode(array('success' => (int) $user->block())));
        case 'delete':
			die(json_encode(array('success' => (int) $user->deleteFromFriends())));

        default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>