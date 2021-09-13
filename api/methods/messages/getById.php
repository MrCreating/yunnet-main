<?php

/**
 * Getting message by id.
*/

// params
$params = [
	'peer_id'     => $_REQUEST['peer_id'],
	'message_ids' => $_REQUEST['message_ids']
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

// if not message_ids provided
if (!$params["message_ids"])
	die(create_json_error(15, 'Some parameters was missing or invalid: message_ids is required'));

$message_ids = explode(',', $params["message_ids"]);
$message_identifiers = [];

foreach ($message_ids as $index => $id) {
	// max 100 ids
	if ($index > 100) break;

	// push only unique ids.
	if (!in_array(intval($id), $message_identifiers) && intval($id) && intval($id) > 0) $message_identifiers[] = intval($id);
}

// resulted response
$result = [];

// connecting modules
if (!function_exists('get_message_array_by_credentials'))
	require __DIR__ . "/../../../bin/functions/messages.php";

$chat_data = parse_id_from_string($params['peer_id']);
$uid       = get_uid_by_lid($connection, $chat_data['chat_id'], $chat_data['is_bot'], $context['user_id']);
if (!$uid)
	die(create_json_error(110, 'This chat is not exists on your account'));

// getting messages
foreach ($message_identifiers as $index => $message_id) {
	$data = get_message_array_by_credentials($connection, $uid, $message_id);
	if ($data)
	{
		$msg = message_to_array($connection, $data, $chat_data);
		if ($msg)
		{
			unset($msg['keyboard']);
			$result[] = $msg;
		}
	}
}

die(json_encode(array(
	'response' => [
		'items' => $result,
		'count' => count($result)
	]
)));
?>