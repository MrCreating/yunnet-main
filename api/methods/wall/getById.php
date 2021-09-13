<?php

/**
 * Get post by id.
*/

// params
$params = [
	'wall_id' => $_REQUEST['wall_id'],
	'post_id' => $_REQUEST['post_id']
];

if ($only_params)
	return $params;
	
if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// wall_id is required
if (!$params['wall_id'])
	die(create_json_error(15, 'Some parameters was missing or invalid: wall_id is required'));

// post_id is required
if (!$params['post_id'])
	die(create_json_error(15, 'Some parameters was missing or invalid: post_id is required'));

// check user's availability
$user_exists = resolve_id_by_name($connection, intval($params['wall_id']) > 0 ? "id".intval($params['wall_id']) : 'bot'.intval($params['wall_id']));
if (!$user_exists)
	die(create_json_error(-9, 'Destination object is not exists'));

// connecting modules
if (!function_exists('can_access_closed'))
	require __DIR__ . '/../../../bin/functions/users.php';
if (!function_exists('get_posts'))
	require __DIR__ . '/../../../bin/functions/wall.php';

// check the blacklist
$me_blacklisted = in_blacklist($connection, intval($params['wall_id']), $context['user_id']);
if ($me_blacklisted)
	die(create_json_error(215, 'You have been blacklisted by this user'));

// check closed profile
$can_access_closed = can_access_closed($connection, $context['user_id'], intval($params['wall_id']));
if (!$can_access_closed)
	die(create_json_error(217, 'This profile is closed'));

// OK! Credentials.
$wall_id = intval($params['wall_id']);
$post_id = intval($params['post_id']);

// getting the post
$post = get_post_by_id($connection, $wall_id, $post_id);
if (!$post)
	die(create_json_error(216, 'Post not found or deleted'));

// sending it.
die(json_encode(['response'=>$post->toArray()]));
?>