<?php

/**
 * New implementation of emit_event function
 * Handle events from realtime server (client-side)
*/

class EventEmitter
{
	public function __construct () 
	{}

	public function sendEvent (?array $user_ids, ?array $local_ids, ?array $event): bool
	{
		$owner_id = intval($_SESSION['user_id']);

		if ($owner_id === 0) return false;

		$socket  = socket_create(AF_UNIX, SOCK_STREAM, 0);
		$noblock = socket_set_nonblock($socket);
		$timeout = socket_set_timeout($socket, 2);
		$connect = socket_connect($socket, __DIR__ . "/managers/sockets/lp_manager.sock");
		
		if (!$connect) return false;

		$result = [
			'user_ids' => $user_ids,
			'lids'     => $local_ids,
			'owner_id' => $owner_id,
			'event'    => $event
		];

		return socket_write($socket, json_encode($result));
	}
}

?>