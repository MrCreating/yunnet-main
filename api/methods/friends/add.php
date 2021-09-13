<?php

/**
 * API for friends adding or requests accepting
*/

$params = [
	'user_id' => intval($_REQUEST['user_id'])
];

if ($only_params)
	return $params;
	
if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// if not user_id provided
if (!$params["user_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: user_id is required'));

$result = resolve_id_by_name($connection, "id".$params['user_id']);
if (!$result)
	die(create_json_error(-9, 'Destination object is not exists'));

if (!function_exists('create_friendship'))
	require __DIR__ . '/../../../bin/functions/users.php';

die(json_encode(array('response'=>intval(create_friendship($connection, $context['user_id'], $params['user_id'])))));
?>