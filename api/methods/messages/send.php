<?php

/**
 * Send message API.
*/

// all params
$params = [
	'peer_id'     => $_REQUEST['peer_id'],
	'text'        => $_REQUEST['text'],
	'attachments' => $_REQUEST['attachments'],
	'fwd'         => $_REQUEST['fwd'],
	'keyboard'    => $_REQUEST['keyboard']
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// connecting functions
if (!function_exists('send_message'))
	require __DIR__ . "/../../../bin/functions/messages.php";

// if not peer_id provided
if (!$params["peer_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: peer_id is required'));

// if not another data provided.
if (!$params["text"] && !$params["attachments"] && !$params["fwd"])
	die(create_json_error(15, 'Some parameters was missing or invalid: text is required'));

// fetching chat credentials
$chat_data = parse_id_from_string($params["peer_id"]);

$sel    = intval($chat_data["chat_id"]);
$is_bot = boolval($chat_data["is_bot"]);
if ($sel > 0)
{
	$result = resolve_id_by_name($connection, $is_bot ? "bot".$sel : "id".$sel);
	if (!$result)
		die(create_json_error(-9, 'Destination object is not exists'));
}

// getting uid by peer
$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context["user_id"]);
if (!$uid && ($context["user_id"] < 0 || $sel < 0))
	die(create_json_error(110, 'This chat is not exists on your account'));

// checking permissions for writing to the chat
$can_write_to_chat = can_write_to_chat($connection, $uid, $context['user_id'], $chat_data);
if ($can_write_to_chat === false)
	die(create_json_error(107, 'You do not have access to this chat'));

if ($can_write_to_chat === 1)
	die(create_json_error(106, 'You have been muted in this chat'));

// Return current user to if he had been leaved it.
if ($can_write_to_chat === 2 && $sel < 0)
{
	if (!class_exists('Chat'))
		require __DIR__ . "/../../../../bin/objects/chats.php";

	$chat = new Chat($connection, $uid);
	$chat->addUser($context["user_id"], $context["user_id"], [
		'chat_id' => $sel,
		'is_bot'  => $is_bot
	]);
}

// send message
$result = send_message($connection, $uid, $context["user_id"], $chat_data, [
	'text'        => $params["text"],
	'attachments' => $params["attachments"],
	'fwd'         => $params["fwd"],
	'keyboard'    => json_decode($params["keyboard"]) ? $params["keyboard"] : NULL,
	'chat_id'     => $sel,
	'is_bot'      => $is_bot
], function ($result) {
	// Here will be local_id for response.

	echo json_encode(array('response'=>$result));
	return fastcgi_finish_request();
});

// Another errors.
if ($result["error"])
{
	$error = $result["error"];
	if ($error === 101)
		die(create_json_error(101, 'Message is too long'));

	if ($error === 102)
		die(create_json_error(102, 'Message is empty or invalid'));

	if ($error === 103)
		die(create_json_error(103, 'You do not have access to this chat'));	

	if ($error === 105)
		die(create_json_error(105, 'Unable to save message'));

	if ($error === 120)
		die(create_json_error(120, 'Keyboards can send only bots'));

	if ($error === 121)
		die(create_json_error(121, 'JSON keyboard parse error.'));

	if ($error === 122)
		die(create_json_error(122, 'Keyboard parse error: Row: '.$result['data'][0].', Column: '.$result['data'][1]));
}

?>