<?php

// large actions handle here.
use unt\objects\Context;
use unt\objects\Request;
use unt\objects\User;
use unt\platform\DataBaseManager;

require_once './bin/functions/users.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'search':
			$query = strval(Request::get()->data["query"]);
			if (is_empty($query) || strlen($query) > 128)
				die('[]');

			$params = [
				'offset'      => intval(Request::get()->data['offset']),
				'count'       => intval(Request::get()->data['count']),
				'online_only' => intval(Request::get()->data['online_only']),
				'search_bots' => intval(Request::get()->data['search_bots']),
				'only_bots'   => intval(Request::get()->data['only_bots'])
			];

			$done   = [];
			$result = search_users($query, $params);

			foreach ($result as $index => $user) {
				$done[] = $user->toArray();
			}	

			die(json_encode($done));
		break;

		case 'hide_request':
			$user_id = intval(Request::get()->data['user_id']);

            $user = User::findById($user_id);

            if (!$user || !$user->hideFriendshipRequest())
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));
		break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>
