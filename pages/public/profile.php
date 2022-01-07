<?php

$selected_user = resolve_id_by_name($connection, REQUESTED_PAGE);
$user = $selected_user['id'] > 0 ? new User($selected_user["id"]) : new Bot($selected_user['id']*-1);

if ($user->getScreenName() && $went_by_id) {
	die(header("Location: /".$user->getScreenName()));
}

require_once __DIR__ . "/../../bin/functions/wall.php";
require_once __DIR__ . "/../../bin/functions/messages.php";
require_once __DIR__ . '/../../bin/functions/users.php';

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	$can_access_closed = can_access_closed($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);
	$in_blacklist      = in_blacklist($connection, $selected_user["id"], ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0));

	switch ($action)
	{
		case 'get_posts':
			if ($context->isLogged() && (!$can_access_closed || $in_blacklist))
				die(json_encode(array('error' => 1)));

			$result = [];

			$posts = get_posts($connection, $selected_user['id'], ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), 20, false, intval(Request::get()->data['offset'])*20);
			foreach ($posts as $index => $post) {
				$result[] = $post->toArray();
			}

			die(json_encode($result));
		break;

		default:
		break;
	}

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action)
	{
		case 'set_new_status':
			if ($context->getCurrentUser() && ($user->getId() !== $context->getCurrentUser()->getId()))
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => intval(set_user_status($connection, $context->getCurrentUser()->getId(), strval(Request::get()->data['new_status']))))));
		break;

		case 'add':
			if ($in_blacklist) die(json_encode(array('error' => 1)));

			$result = create_friendship($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'block':
			$result = block_user($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'delete':
			$result = delete_friendship($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

			die(json_encode(array('success' => $result)));
		break;

		case 'toggle_send_access':
			if ($in_blacklist) die(json_encode(array('error' => 1)));

			$bot_messages_allowed = is_chat_allowed($connection, $context->getCurrentUser()->getId(), $selected_user["id"]*-1);

			toggle_send_access($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user['id']*-1, !$bot_messages_allowed);

			die(json_encode(array('state' => !$bot_messages_allowedy)));
			break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>