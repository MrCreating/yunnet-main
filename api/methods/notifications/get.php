<?php

/**
 * API for notifications getting
*/

$params = [
	'count'  => intval($_REQUEST['count']) ? intval($_REQUEST['count']) : 20,
	'offset' => intval($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0
];

if ($only_params)
	return $params;
	
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists('get_notifications'))
	require __DIR__ . '/../../../bin/functions/notifications.php';

// getting the notifications
$notes = get_notifications($connection, $context['user_id'], $params['offset'], $params['count']);
$response = [];

foreach ($notes as $index => $notification) {
	$response[] = $notification->toArray();
}

// result
die(json_encode(array('items'=>$response)));
?>