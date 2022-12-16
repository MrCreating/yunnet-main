<?php

use unt\objects\Context;
use unt\objects\Request;
use unt\objects\Session;

// handle session actions here
if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));
	
	switch ($action)
	{
		case "end_session":
			$session = new Session(strval(Request::get()->data['session_id']));
			if (!$session->valid())
				die(json_encode(array('error' => 1)));

			$session->end();

			die(json_encode(array('success' => 1)));
		break;
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}
?>