<?php
if (!class_exists('Session'))
	require __DIR__ . '/../../bin/objects/session.php';

// handle session actions here
if (isset($_POST["action"]))
{
	if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));
	
	switch (strtolower($_POST["action"]))
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
}
?>