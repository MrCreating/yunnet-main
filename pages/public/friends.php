<?php
require_once __DIR__ . "/../../bin/functions/users.php";
require_once __DIR__ . '/../../bin/functions/users.php';

// large actions handle here.
if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'search':
			$query = strval(Request::get()->data["query"]);
			if (unt\functions\is_empty($query) || strlen($query) > 128)
				die('[]');

			$params = [
				'offset'      => intval(Request::get()->data['offset']),
				'count'       => intval(Request::get()->data['count']),
				'online_only' => intval(Request::get()->data['online_only']),
				'search_bots' => intval(Request::get()->data['search_bots']),
				'only_bots'   => intval(Request::get()->data['only_bots'])
			];

			$done   = [];
			$result = search_users($connection, $query, $params);

			foreach ($result as $index => $user) {
				$done[] = $user->toArray();
			}	

			die(json_encode($done));
		break;

		case 'hide_request':
			$user_id = intval(Request::get()->data['user_id']);

			$result = hide_friendship_request($connection, $context->getCurrentUser()->getId(), $user_id);
			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));
		break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>