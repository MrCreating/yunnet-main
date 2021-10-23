<?php

/**
 * API for checking if screen_name is in use
*/

$params = [
	'screen_name' => $_REQUEST['screen_name']
];

if ($only_params)
	return $params;

// if not screen_name provided
if (!$params["screen_name"])
	die(create_json_error(15, 'Some parameters was missing or invalid: screen_name is required'));

if (!function_exists('is_screen_used'))
	require __DIR__ . '/../../../bin/functions/alsettings.php';

die(json_encode(array('response'=>intval(Project::isLinkUsed($connection, $params['screen_name'])))));
?>