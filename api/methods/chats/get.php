<?php

/**
 * Get chats list
*/
$params = [
	'offset' => intval($_REQUEST['offset']) <= 0 ? 0 : intval($_REQUEST['offset']),
	'count'  => intval($_REQUEST['count']) >= 1 && intval($_REQUEST['count']) <= 100 ? intval($_REQUEST['count']) : 20
];

if ($only_params)
	return $params;

if (!in_array('2', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// connect modules
if (!function_exists('get_chats'))
	require __DIR__ . '/../../../bin/functions/messages.php';

// return chats listyy
$chats    = get_chats($connection, $context['user_id'], $params['offset'], $params['count']);

die(json_encode(array(
	'items' => $chats
)));
?>