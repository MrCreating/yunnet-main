<?php

require_once __DIR__ . '/../../bin/objects/session.php';

// handle session actions here
if (isset($_POST["action"]))
{
	$action = strtolower($_POST["action"]);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));
	
	switch ($action)
	{
		case "end_session":
			$session = new Session(strval($_POST['session_id']));
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