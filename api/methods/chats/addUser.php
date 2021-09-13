<?php

/**
 * API for inviting entity to chat
*/

$params = [
	'chat_id'   => intval($_REQUEST['chat_id']),
	'entity_id' => intval($_REQUEST['entity_id']) ? intval($_REQUEST['entity_id']) : intval($context['user_id'])
];

if ($only_params)
	return $params;

// bots can not use this method
if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// if not chat_id provided
if (!$params["chat_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: chat_id is required'));

if ($params['chat_id'] < 0)
	die(create_json_error(195, 'Chat id must be positive'));

// connecting modules
if (!class_exists('Chat'))
	require __DIR__ . '/../../../bin/objects/chats.php';
if (!class_exists('is_friends'))
	require __DIR__ . '/../../../bin/functions/users.php';
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
if ($params['entity_id'] !== $context['user_id'])
{
	if ($perms->getValue("can_invite") > $me["flags"]["level"])
	{
		die(create_json_error(175, 'You do not have access to do this action'));
	}

	$object = (($params['entity_id']) > 0 ? new User($params['entity_id']) : new Bot($params['entity_id']*-1));
	if (!$object->isAlive)
		die(create_json_error(-9, 'Destination object is not exists'));

	$settings = json_decode($object->profile['settings'])->privacy->can_invite_to_chats;
	if ($settings > 0)
		die(create_json_error(181, 'You can not invite this user by his privacy settings'));

	if ($params['entity_id'] > 0)
	{
		if (!is_friends($connection, $context['user_id'], $params['entity_id']))
			die(create_json_error(182, 'You can not invite this user'));
	}

	if ($members['users']['user_'.$params['entity_id']])
	{
		if (!$members['users']['user_'.$params['entity_id']]['flags']['is_kicked'] && !$members['users']['user_'.$params['entity_id']]['flags']['is_leaved'])
		{
			die(create_json_error(183, 'You can not invite this user: already in chat'));
		}
		if ($members['users']['user_'.$params['entity_id']]['flags']['is_leaved'])
		{
			die(create_json_error(184, 'You can not invite this user: leaved this chat'));
		}
	}
}

die(json_encode(array(
	'response' => intval($result->addUser($context['user_id'], $params['entity_id'], [
		'actioner_id' => $params['entity_id'],
		'chat_id'     => $params['chat_id']*-1,
		'is_bot'      => false
	]))
)));
?>