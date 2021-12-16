<?php

/**
 * This site modules sends an a service message to chat
 * define $message_type to set the type
 * define $additional to set params array
 * before requiring this module
*/

if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

if (!function_exists('emit_event'))
	require __DIR__ . "/../../../bin/emitters.php";

$chat_data = parse_id_from_string($_REQUEST["s"]);
if (!$chat_data)
	die(json_encode(array('error'=>1)));

$sel    = intval($chat_data["chat_id"]);
$is_bot = boolval($chat_data["is_bot"]);
$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());

if (!$uid && $sel < 0)
	die(json_encode(array('error'=>1)));

$can_write_to_chat = can_write_to_chat($connection, $uid, $context->getCurrentUser()->getId(), $chat_data);
if (!$can_write_to_chat || $can_write_to_chat === 1)
{
	die(json_encode(array('error'=>1)));
}

$params = [
	'actioner_id' => 3,
	'chat_id'     => $sel,
	'is_bot'      => $is_bot
];

die(var_dump(send_service_message($connection, $uid, $context->getCurrentUser()->getId(), "mute_user", $params)));

?>