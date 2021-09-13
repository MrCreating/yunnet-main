<?php

/**
 * API for getting notification by id.
*/

$params = [
	'id' => intval($_REQUEST['id'])
];

if ($only_params)
	return $params;
	
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

if (!$params['id'])
	die(create_json_error(15, 'Some parameters was missing or invalid: id is required'));

if ($params['id'] < 0)
	die(create_json_error(15, 'Some parameters was missing or invalid: id is invalid'));

if (!class_exists('Notification'))
	require __DIR__ . '/../../../bin/functions/notifications.php';

$notification = new Notification($connection, $params['id'], $context['user_id']);
if (!$notification->isValid || $notification->is_read)
	die(create_json_error(394, 'Notification not found'));

die(json_encode(array('response'=>$notification->toArray())));
?>