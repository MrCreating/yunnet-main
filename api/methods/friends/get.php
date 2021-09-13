<?php

/**
 * Get friends API
*/

$params = [
	'user_id'  => intval($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : intval($context['user_id']),
	'extended' => intval($_REQUEST['extended']) ? 1 : 0,
	'fields'   => strval($_REQUEST['fields'])
];

if ($only_params)
	return $params;

if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// check user's availability
$user_exists = resolve_id_by_name($connection, "id".$params['user_id']);
if (!$user_exists)
	die(create_json_error(-9, 'Destination object is not exists'));

// connecting modules
if (!function_exists('get_friends_list'))
	require __DIR__ . '/../../../bin/functions/users.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';

$user = new User($params['user_id']);
if (intval($user->isBanned()))
	die(create_json_error(401, 'This user is banned'));

// check the blacklist
$me_blacklisted = in_blacklist($connection, $params['user_id'], $context['user_id']);
if ($me_blacklisted)
	die(create_json_error(215, 'You have been blacklisted by this user'));

// checking access
$can_access_closed = can_access_closed($connection, $context['user_id'], $params['user_id']);
if (!$can_access_closed)
	die(create_json_error(217, 'This profile is closed'));

// getting friends list
$friends = get_friends_list($connection, $params['user_id'], null, $params['extended']);
$result  = [];

foreach ($friends as $index => $user) {
	$result[] = $user instanceof User ? $user->toArray($params['fields']) : intval($user);
}

die(json_encode(['items'=>$result]));
?>