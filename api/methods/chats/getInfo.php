<?php

/**
 * Getting chat info by peer
*/

$params = [
	'chat_id' => intval($_REQUEST['chat_id']),
	'extended' => intval($_REQUEST['extended']) ? 1 : 0
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// if not chat_id provided
if (!$params["chat_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: chat_id is required'));

// if not chat_id is positive
if ($params["chat_id"] < 0)
	die(create_json_error(195, 'Chat id must be positive'));

// connecting modules
if (!class_exists('Chat'))
	require __DIR__ . '/../../../bin/objects/chats.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';
if (!function_exists('get_uid_by_lid'))
	require __DIR__ . '/../../../bin/functions/messages.php';

$uid = get_uid_by_lid($connection, $params['chat_id']*-1, false, $context['user_id']);
if (!$uid)
	die(create_json_error(110, 'This chat is not exists on your account'));

$can_write_to_chat = can_write_to_chat($connection, $uid, $context['user_id'], ['chat_id' => $params['chat_id']*-1,'is_bot' => false]);
if (!$can_write_to_chat)
	die(create_json_error(107, 'You do not have access to this chat'));

$result  = new Chat($connection, $uid);
$members = $result->getMembers();
$perms   = $result->getPermissions();

$response = [
	'title'       => $result->title,
	'photo_url'   => $result->photo,
	'members'     => [
		'count' => intval($members['count'])
	],
	'permissions' => $perms->getAll()
];

$me = $members['users']['user_'.$context['user_id']];
if ($me['flags']['level'] >= 9)
	$response['join_link'] = $result->getLink();

foreach ($members['users'] as $index => $user) {
	$user_id = intval($user['user_id']);
	if ($params['extended'])
	{
		$user_data = ($user_id > 0 ? new User($connection, $user_id) : new Bot($connection, $user_id*-1))->toArray();

		$user_data['chat_flags'] = [
			'invited_by'   => intval($user['invited_by']),
			'access_level' => intval($user['flags']['level'])
		];
		$response['members']['profiles'][] = $user_data;
	} else
	{
		$response['members']['profiles'][] = $user_id;
	}
}

die(json_encode(array(
	'response' => $response
)));
?>