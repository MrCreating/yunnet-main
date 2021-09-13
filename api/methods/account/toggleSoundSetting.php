<?php

/**
 * API for toggling sound setting
*/

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists('set_user_settings'))
	require __DIR__ . '/../../../bin/functions/users.php';

$result = set_user_settings($connection, $context['user_id'], "sound", intval(!$context['owner_object']->getSettings()->getValues()->notifications->sound));

die(json_encode(array('response'=>intval($result))));
?>