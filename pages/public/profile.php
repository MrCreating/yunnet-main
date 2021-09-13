<?php

$selected_user = resolve_id_by_name($connection, REQUESTED_PAGE);
$user = $selected_user['id'] > 0 ? new User($selected_user["id"]) : new Bot($selected_user['id']*-1);

if (!function_exists('get_posts'))
	require __DIR__ . "/../../bin/functions/wall.php";
if (!function_exists('can_write_to_chat'))
	require __DIR__ . "/../../bin/functions/messages.php";
if (!function_exists('block_user'))
	require __DIR__ . '/../../bin/functions/users.php';

if (isset($_POST['action']))
{
	if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));
	$action = strtolower($_POST['action']);

	$can_access_closed = can_access_closed($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);
	$in_blacklist      = in_blacklist($connection, $selected_user["id"], ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0));

	if ($action === "get_posts")
	{
		if (!$can_access_closed || $in_blacklist)
			die(json_encode(array('error' => 1)));

		$result = [];

		$posts = get_posts($connection, $selected_user['id'], ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), 20, false, intval($_POST['offset'])*20);
		foreach ($posts as $index => $post) {
			$result[] = $post->toArray();
		}

		die(json_encode($result));
	}
	if ($action === "set_new_status")
	{
		if ($context->getCurrentUser() && ($user->getId() !== $context->getCurrentUser()->getId()))
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success'=>intval(set_user_status($connection, $context->getCurrentUser()->getId(), strval($_POST['new_status']))))));
	}
	if ($action === "add")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
		if ($in_blacklist) die(json_encode(array('error' => 1)));

		$result = create_friendship($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

		die(json_encode(array('success'=>$result)));
	}
	if ($action === "block")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));

		$result = block_user($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

		die(json_encode(array('success'=>$result)));
	}
	if ($action === "delete")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));

		$result = delete_friendship($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user["id"]);

		die(json_encode(array('success'=>$result)));
	}
	if ($action === "toggle_send_access")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
		if ($in_blacklist) die(json_encode(array('error' => 1)));

		$bot_messages_allowed = is_chat_allowed($connection, $context->getCurrentUser()->getId(), $selected_user["id"]*-1);

		toggle_send_access($connection, ($context->getCurrentUser() !== NULL ? $context->getCurrentUser()->getId() : 0), $selected_user['id']*-1, !$bot_messages_allowed);

		die(json_encode(array('state'=>!$bot_messages_allowedy)));
	}
}

if ($user->getScreenName() && $went_by_id) {
	die(header("Location: /".$user->getScreenName()));
}

?>