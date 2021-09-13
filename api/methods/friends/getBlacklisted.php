<?php

/**
 * Blacklist get API
*/

$params = [
	'extended' => intval($_REQUEST['extended']) ? 1 : 0
];

if ($only_params)
	return $params;

// bots can not use this method
if (!in_array('1', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists('get_blacklist'))
	require __DIR__ . "/../../../bin/functions/users.php";

$users = get_blacklist($connection, $context["user_id"]);
$result = [];

foreach ($users as $index => $user) 
{
	if ($params['extended'])
	{
		$user_data = $user->toArray();
		$user_data['can_access_closed'] = can_access_closed($connection, $context["user_id"], $user->profile['id']);

		$result[] = $user_data;
	} else
	{
		$result[] = intval($user->profile["id"]);
	}
}

die(json_encode(['items'=>$result]));
?>