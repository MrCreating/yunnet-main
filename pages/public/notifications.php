<?php
require __DIR__ . "/../../bin/functions/notifications.php";

// here we will handle notifications actions
if (isset($_POST["action"]))
{
	$action = strtolower($_POST['action']);

	if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

	switch ($action)
	{
		case "notification_read":
			$id = intval($_POST["notification_id"]);
			if ($id >= 0)
			{
				$notification = new Notification($connection, $id, $context->getCurrentUser()->getId());
				if ($notification->isValid && $notification->read())
					die(json_encode(array('success'=>1)));

				die(json_encode(array('error'=>1)));
			} else {
				die(json_encode(array('success'=>1)));
			}
		break;
		case "notification_hide":
			$id = intval($_POST["notification_id"]);

			if ($id >= 0)
			{
				$notification = new Notification($connection, $id, $context->getCurrentUser()->getId());
				if ($notification->isValid && $notification->hide())
					die(json_encode(array('success'=>1)));

				die(json_encode(array('error'=>1)));
			} else {
				die(json_encode(array('success'=>1)));
			}
		break;
		case "get_notifications":
			$notes = get_notifications($connection, $context->getCurrentUser()->getId(), intval($_POST['offset']), intval($_POST['count']));
			$response = [];

			foreach ($notes as $index => $note) {
				$response[] = $note->toArray();
			}

			die(json_encode($response));
		default:
		break;
	}
}
?>