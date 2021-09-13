<?php
if (!function_exists('emit_event'))
	require __DIR__ . "/../../../bin/emitters.php";

$chat_data = parse_id_from_string($_POST["peer_id"]);
if (!$chat_data)
	die(json_encode(array('error'=>1)));

$sel    = intval($chat_data["chat_id"]);
$is_bot = boolval($chat_data["is_bot"]);
if ($sel > 0)
{
	$result = resolve_id_by_name($connection, $is_bot ? "bot".$sel : "id".$sel);
	if (!$result)
		die(json_encode(array(
			'error' => 1
		)));
}
$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());

if (!$uid && $sel < 0)
	die(json_encode(array('error'=>1)));

$can_write_to_chat = can_write_to_chat($connection, $uid, $context->getCurrentUser()->getId(), $chat_data);
if (!$can_write_to_chat || $can_write_to_chat === 1)
{
	die(json_encode(array('error'=>1)));
}
if ($can_write_to_chat === 2 && $sel < 0)
{
	if (!class_exists('Chat'))
		require __DIR__ . "/../../../bin/objects/chats.php";

	$chat = new Chat($connection, $uid);
	$chat->addUser($context->getCurrentUser()->getId(), $context->getCurrentUser()->getId(), [
		'chat_id' => $sel,
		'is_bot'  => $is_bot
	]);
}

$params = [
	'text'        => $_POST["text"],
	'attachments' => $_POST["attachments"],
	'fwd'         => $_POST["fwd"],
	'chat_id'  => $sel,
	'is_bot'   => $is_bot,
	'keyboard' => NULL,
	'payload'  => is_empty($_POST['payload']) ? NULL : strval($_POST['payload'])
];

$result = send_message($connection, $uid, $context->getCurrentUser()->getId(), $chat_data, $params, 
	function ($result) {
		echo 
			json_encode(
				array(
					'id' => $result
				)
			);

		return fastcgi_finish_request();
	}
);

if ($result["error"])
	die(json_encode(array('error'=>1)));
?>