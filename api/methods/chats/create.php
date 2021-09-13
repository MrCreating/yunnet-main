<?php

/**
 * API for chats creation
*/

$params = [
	'title' => !(is_empty(strval($_REQUEST['title'])) || strlen($_REQUEST['title']) > 64) ? strval($_REQUEST['title']) : NULL,
	'user_ids' => explode(',', $_REQUEST['user_ids'])
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if (!$params["title"])
	die(create_json_error(15, 'Some parameters was missing or invalid: title is invalid'));

$user_identifiers = [];
foreach ($params['user_ids'] as $index => $user_id) {
	$user_id = intval($user_id);
	if ($user_id > 0 && !in_array($user_id, $user_identifiers) && $user_id !== $context['user_id']) $user_identifiers[] = $user_id;
}

// connecting modules
if (!function_exists('is_friends'))
	require __DIR__ . '/../../../bin/objects/chats.php';

$result = create_chat($connection, $context['user_id'], $params['title'], $user_identifiers);
if ($result['error'] === -1)
	die(create_json_error(301, 'Unable to create chat: users count error'));

if ($result['error'] === -2)
	die(create_json_error(302, 'Title is invalid'));

if ($result['error'] === false)
	die(create_json_error(303, 'Unable to create chat'));

die(json_encode(array('response'=>intval($result))));
?>