<?php

/**
 * API for getting chat info by link
*/

$params = [
	'link' => $_REQUEST['link']
];

if ($only_params)
	return $params;

// bots can not use this method
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

$result = [];
parse_str($params['link'], $result);

$query = $result['https://yunnet_ru/chat?q'];
if (!$query)
	die(create_json_error(390, 'Link parse error'));

// connecting modules
if (!function_exists('get_chat_by_query'))
	require __DIR__ . '/../../../bin/functions/chats.php';

$chat = get_chat_by_query($connection, $query, $context['user_id']);
if (!$chat)
	die(create_json_error(391, 'Chat not found or link is not actual'));

$members = $chat->getMembers();
$me      = $members['users']['user_'.$context['user_id']];
if ($me)
{
	die(create_json_error(392, 'You are already in this chat', ['chat_id' => intval($me['local_id'])*-1]));
}

$response = [
	'title'     => $chat->title,
	'photo_url' => $chat->photo,
	'members'     => [
		'count' => intval($members['count'])
	]
];

foreach ($members['users'] as $index => $user) {
	$response['members']['profiles'][] = $user['user_id'];
}

die(json_encode(array(
	'response' => $response
)));
?>