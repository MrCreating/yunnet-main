<?php

/**
 * Get outcoming friends API
*/

$params = [
	'user_id'  => intval($context['user_id']),
	'extended' => intval($_REQUEST['extended']) ? 1 : 0,
	'fields'   => strval($_REQUEST['fields'])
];

if ($only_params)
	return $params;

// bots can not use this method
if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists('get_friends_list'))
	require __DIR__ . '/../../../bin/functions/users.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';

// getting friends list
$friends = get_friends_list($connection, $params['user_id'], "outcoming", $params['extended']);
$result  = [];

foreach ($friends as $index => $user) {
	$result[] = $user instanceof User ? $user->toArray($params['fields']) : intval($user);
}

die(json_encode(['items'=>$result]));
?>