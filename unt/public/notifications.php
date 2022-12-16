<?php

use unt\objects\Context;
use unt\objects\Notification;
use unt\objects\Request;

require_once __DIR__ . "/../../bin/objects/Notification.php";

/**
 * Notifications actions will be here
*/

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action)
	{
		case "notification_read":
			$id = intval(Request::get()->data["notification_id"]);
			if ($id >= 0)
			{
				$notification = new Notification(Context::get()->getCurrentUser()->getId(), $id);

				if ($notification->valid() && $notification->read())
					die(json_encode(array('success' => 1)));
			}
		break;

		case "notification_hide":
			$id = intval(Request::get()->data["notification_id"]);

			if ($id >= 0)
			{
				$notification = new Notification(Context::get()->getCurrentUser()->getId(), $id);

				if ($notification->valid() && $notification->hide())
					die(json_encode(array('success' => 1)));
			}
		break;

		case "get_notifications":
			$notes    = Notification::getList(intval(Request::get()->data['offset']), intval(Request::get()->data['count']));
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