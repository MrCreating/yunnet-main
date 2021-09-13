<?php

/**
 * Screen name parser
*/

// params
$params = [
	'screen_name' => isset($_REQUEST['screen_name']) && !is_empty($_REQUEST['screen_name']) ? strtolower($_REQUEST['screen_name']) : NULL
];

if ($only_params)
	return $params;

// if not screen_name provided
if (!$params["screen_name"])
	die(create_json_error(15, 'Some parameters was missing or invalid: screen_name is required'));

$result = [];
$user = resolve_id_by_name($connection, $params['screen_name']);
if ($user)
{
	$result['id']           = intval($user['id']);
	$result['account_type'] = $user['account_type'];
}

// resolve it.
die(json_encode(array(
	'response' => $result
)));
?>