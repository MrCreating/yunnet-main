<?php

/**
 * This file contains functions,
 * which send data to PollEngine (events for users), 
 * or TextEngine (messages, chats, etc)
*/

if (!class_exists('EventEmitter'))
	require __DIR__ . '/event_manager.php';

// send event to lp server
function emit_event ($user_ids, $lids, $event, $owner_id = 0)
{
	$emitter = new EventEmitter();

	return $emitter->sendEvent($user_ids, $lids, $event);
}

// receiver local_chat_id from uid by text engine
function get_local_chat_id (int $uid)
{
	$socket  = socket_create(AF_UNIX, SOCK_STREAM, 0);
	$block   = socket_set_block($socket);
	$timeout = socket_set_timeout($socket, 5);
	$connect = socket_connect($socket, __DIR__ . "/managers/sockets/text_engine.sock");
	if (!$connect)
		return false;

	$data = [
		'operation' => 'get_lid',
		'uid'       => $uid
	];

	$result = socket_write($socket, json_encode($data));
	if (!$result)
		return false;
	$local_chat_id = socket_read($socket, 25);
	socket_close($socket);

	if (!$local_chat_id)
	{
		return false;
	}

	return $local_chat_id;
}
?>