<?php

namespace unt\platform;

use unt\objects\BaseObject;

/**
 * New implementation of emit_event function
 * Handle events from realtime server (client-side)
*/

class EventEmitter extends BaseObject
{
	public function __construct () 
	{
        parent::__construct();
    }

	public function event (array $event, ?array $user_ids = NULL, ?array $local_ids = NULL): bool
	{
		$user_ids = ($user_ids === NULL ? [intval($_SESSION['user_id'])] : $user_ids);
		$local_ids = ($local_ids === NULL ? [] : $local_ids);

		return $this->sendEvent($user_ids, $local_ids, $event);
	}

	public function sendEvent (?array $user_ids, ?array $local_ids, ?array $event): bool
	{
		$owner_id = intval($_SESSION['user_id']);
		if ($owner_id === 0) return false;

		$poll_engine = curl_init("poll_engine:8080");

		$data = json_encode([
			'event'     => $event,
			'owner_id'  => $owner_id,
			'user_ids'  => $user_ids,
			'local_ids' => $local_ids
		]);

		curl_setopt($poll_engine, CURLOPT_POSTFIELDS,     $data);
		curl_setopt($poll_engine, CURLOPT_POST,           1);
		curl_setopt($poll_engine, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));
		curl_setopt($poll_engine, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($poll_engine);
		curl_close($poll_engine);

		return boolval(intval($result));
	}
}

?>