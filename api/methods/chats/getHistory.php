<?php

/**
 * Get chats history
*/
// params
$params = [
	'peer_id' => $_REQUEST['peer_id'],
	'offset'  => intval($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0,
	'count'   => intval($_REQUEST['count']) <= 1000 && intval($_REQUEST['count']) > 0 ? intval($_REQUEST['count']) : 100,
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// if not peer_id provided
if (!$params["peer_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: peer_id is required'));

// connecting modules
if (!function_exists('parse_id_from_string'))
	require __DIR__ . '/../../../bin/functions/messages.php';

$chat_data = parse_id_from_string($params['peer_id']);

$sel    = intval($chat_data["chat_id"]);
$is_bot = boolval($chat_data["is_bot"]);
if ($sel > 0)
{
	$result = resolve_id_by_name($connection, $is_bot ? "bot".$sel : "id".$sel);
	if (!$result)
		die(create_json_error(-9, 'Destination object is not exists'));
}

$uid           = get_uid_by_lid($connection, $sel, $is_bot, $context['user_id']);
$messages_list = get_chat_messages($connection, $uid, $context['user_id'], $params['offset'], $params['count'], $chat_data);

if (!$messages_list)
	$messages_list = [];

die(json_encode(array('items'=>$messages_list)));
?>