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
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$accountSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('account');

		die(json_encode(array('success' => intval($accountSettings->setLanguageId($_POST['lang'])->getLanguageId() === strtolower($_POST['lang'])))));
	}

	if ($action === 'set_privacy_settings')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$privacySettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('privacy');

		$groups = [
			1 => 'can_write_messages',
			2 => 'can_write_on_wall',
			3 => 'can_invite_to_chats',
			4 => 'can_comment_posts'
		];

		$group  = $groups[intval($_POST['group'])];
		$value  = intval($_POST['value']);
		$result = intval($privacySettings->setGroupValue($group, $value)->getGroupValue($group) === $value);

		if (!$result)
			die(json_encode(array('error' => 1)));

		die(json_encode(array('success' => 1)));
	}
	if ($action === 'toggle_profile_state')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$accountSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('account');

		die(json_encode(array('success' => intval($accountSettings->setProfileClosed(!$accountSettings->isProfileClosed())->isProfileClosed()))));
	}
	if ($action === 'toggle_push_settings')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$settingsGroup = strval($_POST['settings_group']);
		$groups = ['notifications', 'sound'];

		if (!in_array($settingsGroup, $groups))
			die(json_encode(array('error' => 1)));

		$new_value = boolval(intval($_POST['new_value']));
		
		$pushSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('push');
		if ($settingsGroup === $groups[0])
			$result = $pushSettings->setNotificationsEnabled($new_value);
		if ($settingsGroup === $groups[1])
			$result = $pushSettings->setSoundEnabled($new_value);

		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('response' => $new_value)));
	}
	if ($action === 'get_blacklisted')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

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
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		if (!function_exists('check_password'))
			require __DIR__ . '/../../bin/functions/auth.php';

		$password = strval($_POST['password']);

		$result = check_password($connection, $context->getCurrentUser()->getId(), $password);

		die(json_encode(array('state'=>intval($result))));
	}
	if ($action === 'change_password')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		if (!function_exists('check_password'))
			require __DIR__ . '/../../bin/functions/auth.php';

		$oldPassword = strval($_POST['old_password']);
		$newPassword = strval($_POST['new_password']);

		if (!check_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword))
			die(json_encode(array('error'=>1)));

		$result = change_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword);
		if (!$result) die(json_encode(array('error'=>1)));

		$context->writeSessions([]);

		die(json_encode(array('state' => 1)));
	}
	if ($action === 'get_accounts')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		if (!function_exists('get_accounts'))
			require __DIR__ . '/../../bin/functions/accounts.php';

		$accounts_list = get_accounts($connection, $context->getCurrentUser()->getId());

		die(json_encode(array('response' => $accounts_list)));
	}
	if ($action === 'update_menu_items')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$items  = explode(',', strval($_POST['items']));

		$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');
		$success         = $themingSettings->setMenuItemIds($items);

		if (!$success)
			die(json_encode(array('error' => 1)));

		die(json_encode(array('success' => 1)));
	}
	if ($action === 'toggle_js_state')
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');

		$result = intval($themingSettings->setJSAllowance(!$themingSettings->isJSAllowed())->isJSAllowed());

		die(json_encode(array('success' => $result)));
	}
	if ($action === "get_sessions_list")
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

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
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

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
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$gender = intval($_POST['gender']);

		if ($gender !== 1 && $gender !== 2)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('success' => intval($connection->prepare("UPDATE users.info SET gender = ? WHERE id = ? LIMIT 1;")->execute([$gender, $context->getCurrentUser()->getId()])))));
	}
	if ($action === "toggle_new_design")
	{
		if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

		$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');

		$result = intval($themingSettings->useNewDesign(!$themingSettings->isNewDesignUsed())->isNewDesignUsed());

		die(json_encode(array('success' => $result)));
	}

	die(json_encode(array('error' => 1)));
}

?>