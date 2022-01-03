<?php

require_once __DIR__ . '/event_manager.php';

/**
 * This file contains functions,
 * which send data to PollEngine (events for users), 
 * or TextEngine (messages, chats, etc)
*/

// send event to lp server
function emit_event ($user_ids, $lids, $event, $owner_id = 0)
{
	$emitter = new EventEmitter();

	return $emitter->sendEvent($user_ids, $lids, $event);
}

// receiver local_chat_id from uid by text engine
function get_local_chat_id (int $uid)
{
	$text_engine_init = curl_init("text_engine");

	$data = json_encode([
		'operation' => 'get_lid',
		'uid'       => $uid
	]);

	curl_setopt($text_engine_init, CURLOPT_POSTFIELDS,     $data);
	curl_setopt($text_engine_init, CURLOPT_POST,           1);
	curl_setopt($text_engine_init, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));
	curl_setopt($text_engine_init, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($text_engine_init);
	curl_close($result);

	return intval($result);
}
?>