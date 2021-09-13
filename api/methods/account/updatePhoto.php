<?php

/**
 * API for photo updating
*/

$params = [
	'photo' => is_empty($_REQUEST['photo']) ? NULL : strval($_REQUEST['photo'])
];

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists("update_user_photo"))
	require __DIR__ . "/../../../bin/functions/users.php";

// if photo is empty - it must be deleted.
if (!$params['photo'])
{
	die(json_encode(array('response'=>intval(delete_user_photo($connection, $context['user_id'])))));
}

$result = update_user_photo($connection, $context['user_id'], $params['photo']);
if (!$result)
	die(create_json_error(375, 'Unable to set photo'));

die(json_encode(array('response'=>1)));
?>