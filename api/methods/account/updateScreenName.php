<?php

/**
 * API for screen_name changing
*/

$params = [
	'screen_name' => $_REQUEST['screen_name']
];

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// if not screen_name provided
if (!$params["screen_name"])
	die(create_json_error(15, 'Some parameters was missing or invalid: screen_name is required'));

if (!function_exists('update_screen_name'))
	require __DIR__ . '/../../../bin/functions/alsettings.php';

$editor = Context::get()->getCurrentUser()->edit();

if (!$editor)
	die(create_json_error(373, 'Failed to get user editor. Are you sure logged in?'));

$result = $editor->setScreenName($params['screen_name']);

if ($result === -1)
	die(create_json_error(370, 'This screen name is already in use'));

if ($result === 0)
	die(create_json_error(372, 'Screen name contains invalid characters'));

die(json_encode(array('response'=>intval($result))));
?>