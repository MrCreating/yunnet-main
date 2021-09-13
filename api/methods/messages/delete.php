<?php

/**
 * API for messaging deletion
*/

$params = [
	'peer_id'        => $_REQUEST['peer_id'],
	'message_ids'    => $_REQUEST['message_ids'],
	'delete_for_all' => (intval($_REQUEST['delete_for_all']) ? 1 : 0)
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// if not peer_id provided
if (!$params["peer_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: peer_id is required'));

// if not message_ids provided
if (!$params["message_ids"])
	die(create_json_error(15, 'Some parameters was missing or invalid: message_ids is required'));

// connecting modules
if (!function_exists('parse_id_from_string'))
	require __DIR__ . '/../../../bin/functions/messages.php';
if (!class_exists('Chat'))
	require __DIR__ . "/../../../bin/objects/chats.php";

$chat_data = parse_id_from_string($params['peer_id']);
$sel       = intval($chat_data["chat_id"]);
$is_bot    = boolval($chat_data["is_bot"]);

$uid       = get_uid_by_lid($connection, $sel, $is_bot, $context['user_id']);
if (!$uid)
	die(create_json_error(110, 'This chat is not exists on your account'));

$message_ids = explode(',', $params["message_ids"]);
$message_identifiers = [];

if (count($message_ids) > 500)
	die(create_json_error(333, 'Maximum 100 valid messages'));

foreach ($message_ids as $index => $id) {
	// max 100 ids
	if ($index > 100) break;

	// push only unique ids.
	if (!in_array(intval($id), $message_identifiers) && intval($id) && intval($id) > 0) $message_identifiers[] = intval($id);
}

$chat    = new Chat($connection, $uid);
$members = $chat->getMembers();
$perms   = $chat->getPermissions();

$me = $members['users']['user_'.$context['user_id']];

if ($params['delete_for_all'])
{
	if ($uid < 0)
	{
		if (!$chat->isValid)
			die(create_json_error(110, 'This chat is not exists on your account'));

		if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
			die(create_json_error(107, 'You do not have access to this chat'));
	}
}

if (count($message_identifiers) > 500)
	die(create_json_error(333, 'Maximum 100 valid messages'));

$result = delete_messages($connection, $uid, $context['user_id'], $message_identifiers, $params['delete_for_all'], [
	'chat_id' => $sel,
	'is_bot'  => $is_bot
], $perms, $me);

die(json_encode($result));
?>