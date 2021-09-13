<?php

/**
 * get user's wall.
*/

// params
$params = [
	'user_id' => !intval($_REQUEST['user_id']) ? intval($context['user_id']) : intval($_REQUEST['user_id']),
	'offset'  => intval($_REQUEST['offset']),
	'count'   => intval($_REQUEST['count']) ? intval($_REQUEST['count']) : 50
];

if ($only_params)
	return $params;
	
if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// check user's availability
$user_exists = resolve_id_by_name($connection, $params['user_id'] > 0 ? "id".$params['user_id'] : 'bot'.$params['user_id']);
if (!$user_exists)
	die(create_json_error(-9, 'Destination object is not exists'));

// connecting modules
if (!function_exists('can_access_closed'))
	require __DIR__ . '/../../../bin/functions/users.php';
if (!function_exists('get_posts'))
	require __DIR__ . '/../../../bin/functions/wall.php';
if (!class_exists('User'))
	require __DIR__ . '/../../../bin/objects/entities.php';

$user = new User($params['user_id']);
if (intval($user->isBanned()))
	die(create_json_error(401, 'This user is banned'));

// check the blacklist
$me_blacklisted = in_blacklist($connection, $params['user_id'], $context['user_id']);
if ($me_blacklisted)
	die(create_json_error(215, 'You have been blacklisted by this user'));

// check closed profile
$can_access_closed = can_access_closed($connection, $context['user_id'], $params['user_id']);
if (!$can_access_closed)
	die(create_json_error(217, 'This profile is closed'));

// getting the posts
$posts = get_posts($connection, $params['user_id'], $context['user_id'], $params['count'], false, intval($params['offset']));

$posts_resulted = [];
foreach ($posts as $index => $post) {
	$posts_resulted[] = $post->toArray();
}

// show posts
die(json_encode(array(
	'response' => [
		'items' => $posts_resulted,
		'count' => count($posts)
	]
)));
?>