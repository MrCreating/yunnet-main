<?php

/**
 * Search users API.
*/

$params = [
	'q'      => strval($_REQUEST['q']),
	'offset' => intval($_REQUEST['offset']),
	'count'  => intval($_REQUEST['count']) > 0 ? intval($_REQUEST['count']) : 50,
	'fields' => strval($_REQUEST['fields'])
];

if ($only_params)
	return $params;

// if not q provided
if (!$params["q"])
	die(create_json_error(15, 'Some parameters was missing or invalid: q is required'));

// q must not be empty and long
if (is_empty($params["q"]) || strlen($params['q']) > 128)
	die(create_json_error(15, 'Some parameters was missing or invalid: q is invalid'));

// connecting modules
if (!function_exists('search_users'))
	require __DIR__ . '/../../../bin/functions/users.php';

$result   = search_users($connection, $params['q'], [
	'offset' => $params['offset'],
	'count'  =>$params['count']
]);
$response = [];
foreach ($result as $index => $user) {
	// only requested count
	if ($index >= $params['count']) break;

	$response[] = $user->toArray($params['fields']);
}

die(json_encode(array('response'=>[
	'items' => $response,
	'count' => count($result)
])));
?>