<?php

/**
 * API for joining to chat by link
*/

$params = [
	'link' => $_REQUEST['link']
];

if ($only_params)
	return $params;

// bots can not use this method
if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));
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

$owner_id_of_chat = 0;
foreach ($members['users'] as $index => $user) {
	if ($user['flags']['level'] >= 9)
	{
		$owner_id_of_chat = intval($user['user_id']); break;
	}
}

$lid = $chat->addUser($owner_id_of_chat, $context['user_id'], [
	'join_by_link' => true
]);

die(json_encode(array('response'=>$lid)));
?>