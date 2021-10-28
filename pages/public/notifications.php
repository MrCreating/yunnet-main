<?php

require_once __DIR__ . "/../../bin/objects/notification.php";

/**
 * Notifications actions will be here
*/

if (isset($_POST["action"]))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action)
	{
		case "notification_read":
			$id = intval($_POST["notification_id"]);
			if ($id >= 0)
			{
				$notification = new Notification(Context::get()->getCurrentUser()->getId(), $id);

				if ($notification->valid() && $notification->read())
					die(json_encode(array('success' => 1)));
			}
		break;

		case "notification_hide":
			$id = intval($_POST["notification_id"]);

			if ($id >= 0)
			{
				$notification = new Notification(Context::get()->getCurrentUser()->getId(), $id);

				if ($notification->valid() && $notification->hide())
					die(json_encode(array('success' => 1)));
			}
		break;

		case "get_notifications":
			$notes    = Notification::getList(intval($_POST['offset']), intval($_POST['count']));
			$response = [];

			foreach ($notes as $index => $note) {
				$response[] = $note->toArray();
			}

			die(json_encode($response));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>