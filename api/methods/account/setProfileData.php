<?php

/**
 * API for profile editing
*/

$params = [
	'group' => intval($_REQUEST['group']),
	'value' => strval($_REQUEST['value'])
];

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// if not group provided
if (!$params["group"])
	die(create_json_error(15, 'Some parameters was missing or invalid: group is required'));

if (is_empty($params['value']))
	die(create_json_error(15, 'Some parameters was missing or invalid: value is required'));

$groups = [
	1 => 'first_name',
	2 => 'last_name'
];

if (!$groups[$params['group']])
	die(create_json_error(357, 'This group is not found'));

if (!function_exists('update_user_data'))
	require __DIR__ . '/../../../bin/functions/users.php';

die(json_encode(array('response'=>intval(update_user_data($connection, $context['user_id'], $groups[$params['group']], $params['value'])))));
?>