<?php

/**
 * Users get API
*/

// default params
$params = [
	'user_ids' => $_REQUEST["user_ids"],
	'fields'   => $_REQUEST["fields"]
];

if ($only_params)
	return $params;

// connecting modules
if (!class_exists('Entity'))
	require __DIR__ . '/../../../bin/objects/entities.php';
if (!class_exists('can_access_closed'))
	require __DIR__ . '/../../../bin/functions/users.php';

// user_ids
$user_ids = explode(',', $params["user_ids"]);
$length   = count($user_ids);

if ($length === 1 && $user_ids[0] === "")
	$user_ids[0] = intval($context["user_id"]);

// saving unique ids.
$user_identifiers = [];
foreach ($user_ids as $index => $user_id)
{
	// max 100 users
	if ($index > 100) break;

	// push only unique ids.
	if (!in_array(intval($user_id), $user_identifiers) && intval($user_id)) $user_identifiers[] = intval($user_id);
}

// resulted response
$result = [];

foreach ($user_identifiers as $index => $user_id) 
{
	$user = $user_id > 0 ? new User($user_id) : new Bot($user_id*-1);

	// checking validity
	if (!$user->valid()) continue;

	// final user array;
	$user_data = $user->toArray($params["fields"]);

	// done!
	$result[] = $user_data;
}

// return response
die(json_encode(
	['items' => $result]
));
?>