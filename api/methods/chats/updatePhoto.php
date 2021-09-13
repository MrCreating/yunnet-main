<?php

/**
 * API for chat data editing.
*/

$params = [
	'photo'   => is_empty($_REQUEST['photo']) ? NULL : strval($_REQUEST['photo']),
	'chat_id' => intval($_REQUEST['chat_id'])
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

// connecting modules
if (!class_exists('Chat'))
	require __DIR__ . '/../../../bin/objects/chats.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';
if (!function_exists('get_uid_by_lid'))
	require __DIR__ . '/../../../bin/functions/messages.php';
if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/../../../bin/objects/attachment.php';

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
if ($my_access_level >= $perms->getValue("can_change_photo"))
{
	if ($params['photo'])
	{
		$attachment = (new AttachmentsParser())->getObject(strval($params['photo']));
		if (!$attachment)
			die(create_json_error(310, 'Photo is incorrect'));

		$res = $result->updatePhoto($attachment, $context['user_id'], [
			'chat_id'   => $params['chat_id']*-1,
			'is_bot'    => false,
			'new_src'   => $attachment->url,
			'new_query' => $attachment->query
		]);
	} else {
		$res = $result->updatePhoto(null, $context['user_id'], [
			'chat_id'   => $params['chat_id']*-1,
			'is_bot'    => false
		]);
	}

	if (!$res)
		die(create_json_error(315, 'Unable update chat data'));

	die(json_encode(array('response'=>intval($res))));
}

die(create_json_error(175, 'You do not have access to do this action'));
?>