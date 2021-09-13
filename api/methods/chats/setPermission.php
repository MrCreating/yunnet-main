<?php

/**
 * API for permissions management
*/

$params = [
	'permission' => strtolower($_REQUEST['permission']),
	'new_value'  => intval($_REQUEST['new_value']),
	'chat_id'    => intval($_REQUEST['chat_id'])
];

if ($only_params)
	return $params;
	
if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// if not chat_id provided
if (!$params["chat_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: chat_id is required'));
if ($params['chat_id'] < 0)
	die(create_json_error(195, 'Chat id must be positive'));

// if not permission provided
if (!$params["permission"])
	die(create_json_error(15, 'Some parameters was missing or invalid: permission is required'));

// if not new_value provided
if (!$params["new_value"])
	die(create_json_error(15, 'Some parameters was missing or invalid: new_value is required'));

if ($params['new_value'] < 0 || $params['new_value'] > 9)
	die(create_json_error(321, 'Permissions level must be from 0 to 8'));

// connecting modules
if (!class_exists('Chat'))
	require __DIR__ . '/../../../bin/objects/chats.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';
if (!function_exists('get_uid_by_lid'))
	require __DIR__ . '/../../../bin/functions/messages.php';

// getting uid of chat
$uid = get_uid_by_lid($connection, $params['chat_id']*-1, false, $context['user_id']);
if (!$uid)
	die(create_json_error(110, 'This chat is not exists on your account'));

$can_write_to_chat = can_write_to_chat($connection, $uid, $context['user_id'], ['chat_id' => $params['chat_id']*-1,'is_bot' => false]);
if (!$can_write_to_chat)
	die(create_json_error(107, 'You do not have access to this chat'));

$result  = new Chat($connection, $uid);
$members = $result->getMembers();
$perms   = $result->getPermissions();

$me = $members['users']['user_'.$context['user_id']];
if (!$me || $me['flags']['is_leaved'])
	die(create_json_error(107, 'You do not have access to this chat'));

$my_access_level = intval($me['flags']['level']);
if ($my_access_level < 9)
	die(create_json_error(175, 'You do not have access to do this action'));

die(json_encode(array('response'=>intval($perms->setValue($params['permission'], $params['new_value'])))));
?>
?>