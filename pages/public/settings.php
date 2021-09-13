<?php

/**
 * Here we will handle actions with settings.
*/

if (isset($_POST["action"]))
{
	$action = strtolower($_POST['action']);
	if ($action === "logout")
	{
		header('Access-Control-Allow-Origin: '.get_page_origin());
		header('Access-Control-Allow-Credentials: true');

		if (!$context->isLogged())
			die(json_encode(array('success' => 1)));

		$context->Logout();

		die(json_encode(array('success' => 1)));
	}
	if ($action === "change_language")
	{
		session_start();

		$lang_id = strtolower($_POST['lang']);

		$lang = 'en';
		$langs = [
			'ru', 'en'
		];

		if (in_array($lang_id, $langs))
			$lang = $lang_id;

		$_SESSION['lang'] = $lang;
		if ($context->isLogged()) 
		{
			$settings = $context->getCurrentUser()->getSettings()->getValues();
			$settings->lang = $lang;

			$connection->prepare("UPDATE users.info SET settings = ? WHERE id = ? LIMIT 1;")->execute([
				json_encode($settings),
				strval($context->getCurrentUser()->getId())
			]);
		}

		die(json_encode(array('success' => 1)));
	}

	if ($action === 'set_privacy_settings')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('set_privacy_settings'))
			require __DIR__ . '/../../bin/functions/users.php';

		$res = set_privacy_settings($connection, $context->getCurrentUser()->getId(), intval($_POST["group"]), intval($_POST["value"]));
		if (!$res) die(json_encode(array('error'=>1)));

		die(json_encode(array('success'=>1)));
	}
	if ($action === 'toggle_profile_state')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		$settings = $context->getCurrentUser()->getSettings()->getValues();
		$settings->closed_profile = !$settings->closed_profile;

		$encoded_settings = json_encode($settings);
		$user_id          = intval($context->getCurrentUser()->getId());

		$res = $connection->prepare("UPDATE users.info SET settings = :settings WHERE id = :id LIMIT 1;");
		$res->bindParam(":settings", $encoded_settings, PDO::PARAM_STR);
		$res->bindParam(":id",       $user_id,          PDO::PARAM_INT);

		die(json_encode(array('success'=>intval($res->execute()))));
	}
	if ($action === 'toggle_push_settings')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('set_user_settings'))
			require __DIR__ . '/../../bin/functions/users.php';

		$settingsGroup = strval($_POST['settings_group']);
		$groups = ['notifications', 'sound'];

		if (!in_array($settingsGroup, $groups))
			die(json_encode(array('error'=>1)));

		$new_value = intval(boolval(intval($_POST['new_value'])));
		$result = set_user_settings($connection, $context->getCurrentUser()->getId(), $settingsGroup, $new_value);
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('response'=>$new_value)));
	}
	if ($action === 'get_blacklisted')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('get_blacklist'))
			require __DIR__ . '/../../bin/functions/users.php';

		$users  = get_blacklist($connection, $context->getCurrentUser()->getId(), intval($_POST['count']), intval($_POST['offset']));
		$result = [];

		foreach ($users as $key => $value) {
			$result[] = $value->toArray();
		}

		die(json_encode($result));
	}
	if ($action === 'verify_password')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('check_password'))
			require __DIR__ . '/../../bin/functions/auth.php';

		$password = strval($_POST['password']);

		$result = check_password($connection, $context->getCurrentUser()->getId(), $password);

		die(json_encode(array('state'=>intval($result))));
	}
	if ($action === 'change_password')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('check_password'))
			require __DIR__ . '/../../bin/functions/auth.php';

		$oldPassword = strval($_POST['old_password']);
		$newPassword = strval($_POST['new_password']);

		if (!check_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword))
			die(json_encode(array('error'=>1)));

		$result = change_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword);
		if (!$result) die(json_encode(array('error'=>1)));

		$context->writeSessions([]);

		die(json_encode(array('state'=>1)));
	}
	if ($action === 'get_accounts')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('get_accounts'))
			require __DIR__ . '/../../bin/functions/accounts.php';

		$accounts_list = get_accounts($connection, $context->getCurrentUser()->getId());

		die(json_encode(array('response'=>$accounts_list)));
	}
	if ($action === 'update_menu_items')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		$items  = explode(',', strval($_POST['items']));
		$result = [];

		foreach ($items as $index => $itemId) {
			if (intval($itemId) < 0 || intval($itemId) > 8) die(json_encode(array('error'=>1)));

			$result[] = intval($itemId);
		}

		if (!function_exists('set_menu_items'))
			require __DIR__ . "/../../bin/functions/theming.php";

		$result = set_menu_items($connection, $context->getCurrentUser()->getId(), $result);
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success'=>1)));
	}
	if ($action === 'toggle_js_state')
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!function_exists('toggle_js_allowance'))
			require __DIR__ . "/../../bin/functions/theming.php";
		
		die(json_encode(array('state'=>toggle_js_allowance($connection, $context->getCurrentUser()->getId()))));
	}
	if ($action === "get_sessions_list")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!class_exists('Session'))
			require __DIR__ . '/../../bin/objects/session.php';

		$sessions_list = Session::getList();

		$sessions = [];
		foreach ($sessions_list as $index => $session) 
		{
			$sessions[] = $session->toArray();
		}

		die(json_encode($sessions));
	}
	if ($action === "end_session")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		if (!class_exists('Session'))
			require __DIR__ . '/../../bin/objects/session.php';

		$session = new Session(strval($_POST['session_id']));
		if (!$session->valid() || !$session->isCloseable())
			die(json_encode(array('error' => 1)));

		$session->end();

		die(json_encode(array('success' => 1)));
	}
	if ($action === "set_gender")
	{
		if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
		if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

		$gender = intval($_POST['gender']);

		if ($gender !== 1 && $gender !== 2)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success' => intval($connection->prepare("UPDATE users.info SET gender = ? WHERE id = ? LIMIT 1;")->execute([$gender, $context->getCurrentUser()->getId()])))));
	}
}

?>