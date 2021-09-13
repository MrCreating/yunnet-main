<?php

/**
 * API for set privacy settings
*/

$params = [
	'group' => intval($_REQUEST['group']),
	'value' => intval($_REQUEST['value']) ? intval($_REQUEST['value']) : 0
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

// connecting modules
if (!function_exists('set_privacy_settings'))
	require __DIR__ . '/../../../bin/functions/users.php';

die(json_encode(array('response'=>intval(set_privacy_settings($connection, $context['user_id'], $params['group'], $params['value'])))));
?>