<?php
require_once __DIR__ . "/../../bin/functions/users.php";
require_once __DIR__ . '/../../bin/functions/users.php';

// large actions handle here.
if (isset($_POST["action"]))
{
	$action = strtolower($_POST["action"]);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'search':
			$query = strval($_POST["query"]);
			if (is_empty($query) || strlen($query) > 128)
				die('[]');

			$params = [
				'offset'      => intval($_POST['offset']),
				'count'       => intval($_POST['count']),
				'online_only' => intval($_POST['online_only']),
				'search_bots' => intval($_POST['search_bots']),
				'only_bots'   => intval($_POST['only_bots'])
			];

			$done   = [];
			$result = search_users($connection, $query, $params);

			foreach ($result as $index => $user) {
				$done[] = $user->toArray();
			}	

			die(json_encode($done));
		break;

		case 'hide_request':
			$user_id = intval($_POST['user_id']);

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