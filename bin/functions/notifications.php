<?php

namespace unt\functions\notifications;

use unt\objects\Notification;
use unt\objects\User;
use unt\platform\DataBaseManager;

/**
 * function for notification creation
 * returns Notificaions class if notifications created
 * or false if error will happen
 * @deprecated
*/
function create_notification ($connection, $to_id, $type, $data): Notification
{
	$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? ORDER BY local_id DESC LIMIT 1;");
	$res->execute([intval($to_id)]);

	// getting local_id of new notifications
	$new_local_id = intval($res->fetch(PDO::FETCH_ASSOC)["local_id"])+1;

	$res = DataBaseManager::getConnection()->prepare("INSERT INTO users.notes (owner_id, local_id, type, data, is_read) VALUES (:owner_id, :local_id, :type, :data, 0);");

	$encoded_data = json_encode($data);

	$res->bindParam(":owner_id", $to_id,        PDO::PARAM_INT);
	$res->bindParam(":local_id", $new_local_id, PDO::PARAM_INT);
	$res->bindParam(":type",     $type,         PDO::PARAM_STR);
	$res->bindParam(":data",     $encoded_data, PDO::PARAM_STR);

	if ($res->execute())
		$result = new Notification($to_id, $new_local_id);

	if ($result)
	{
		$user = new User($to_id);

		// emit event if it allow settings.
		if ($user->getSettings()->getSettingsGroup('push')->isNotificationsEnabled())
		{
            (new \unt\platform\EventEmitter())->sendEvent([$to_id], [0], [
				'event'        => 'new_notification',
				'notification' => [
					'id'   => $new_local_id,
					'type' => $type,
					'data' => $data
				]
			]);
		}
	}

	// return Notification class
	return $result;
}

?>