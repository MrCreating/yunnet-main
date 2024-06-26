<?php

/**
 * This is a current portfolio
*/

use unt\objects\Request;
use unt\platform\DataBaseManager;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) {
		case 'get_stats':
			/**
			 * Registered users count
			*/
			$res = DataBaseManager::getConnection()->prepare("SELECT id FROM users.info ORDER BY id DESC LIMIT 1;");
			if ($res->execute())
				$registered_users = intval($res->fetch(PDO::FETCH_ASSOC)["id"]);

			/**
			 * Registered bots count
			*/
			$res = DataBaseManager::getConnection()->prepare("SELECT id FROM bots.info ORDER BY id DESC LIMIT 1;");
			if ($res->execute())
				$registered_bots = intval($res->fetch(PDO::FETCH_ASSOC)["id"]);

			/**
			 * Sent messages count
			*/
			$res = DataBaseManager::getConnection()->prepare("SELECT local_id FROM messages.chat_engine_1 ORDER BY local_id DESC LIMIT 1;");
			if ($res->execute())
				$sent_messages = intval($res->fetch(PDO::FETCH_ASSOC)["local_id"]);

			die(json_encode(array(
				'users'    => intval($registered_users),
				'messages' => intval($sent_messages),
				'bots'     => intval($registered_bots)
			)));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>