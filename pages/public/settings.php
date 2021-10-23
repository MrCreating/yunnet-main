<?php

require_once __DIR__ . '/../../bin/functions/users.php';
require_once __DIR__ . '/../../bin/functions/auth.php';
require_once __DIR__ . '/../../bin/functions/accounts.php';

/**
 * Here we will handle actions with settings.
*/

if (isset($_POST["action"]))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'logout':
			header('Access-Control-Allow-Origin: '.get_page_origin());
			header('Access-Control-Allow-Credentials: true');

			$context->Logout();

			die(json_encode(array('success' => 1)));
		break;
		
		case 'change_language':
			$accountSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('account');

			die(json_encode(array('success' => intval($accountSettings->setLanguageId($_POST['lang'])->getLanguageId() === strtolower($_POST['lang'])))));
		break;

		case 'set_privacy_settings':
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
		break;

		case 'toggle_profile_state':
			$accountSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('account');

			die(json_encode(array('success' => intval($accountSettings->setProfileClosed(!$accountSettings->isProfileClosed())->isProfileClosed()))));
		break;

		case 'toggle_push_settings':
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
		break;

		case 'get_blacklisted':
			$users  = get_blacklist($connection, $context->getCurrentUser()->getId(), intval($_POST['count']), intval($_POST['offset']));
			$result = [];

			foreach ($users as $key => $value) {
				$result[] = $value->toArray();
			}

			die(json_encode($result));
		break;

		case 'verify_password':
			$password = strval($_POST['password']);

			$result = check_password($connection, $context->getCurrentUser()->getId(), $password);

			die(json_encode(array('state' => intval($result))));
		break;

		case 'change_password':
			$oldPassword = strval($_POST['old_password']);
			$newPassword = strval($_POST['new_password']);

			if (!check_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword))
				die(json_encode(array('error' => 1)));

			$result = change_password($connection, $context->getCurrentUser()->getId(), $oldPassword, $newPassword);
			if (!$result) die(json_encode(array('error' => 1)));

			$sessions = Session::getList();
			foreach ($sessions as $index => $session) {
				$session->end();
			}

			die(json_encode(array('state' => 1)));
		break;

		case 'get_accounts':
			$accounts_list = get_accounts($connection, $context->getCurrentUser()->getId());

			die(json_encode(array('response' => $accounts_list)));
		break;

		case 'update_menu_items':
			$items  = explode(',', strval($_POST['items']));

			$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');
			$success         = $themingSettings->setMenuItemIds($items);

			if (!$success)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));
		break;

		case 'toggle_js_state':
			$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');

			$result = intval($themingSettings->setJSAllowance(!$themingSettings->isJSAllowed())->isJSAllowed());

			die(json_encode(array('success' => $result)));
		break;

		case 'get_sessions_list':
			$sessions_list = Session::getList();

			$sessions = [];
			foreach ($sessions_list as $index => $session) 
			{
				$sessions[] = $session->toArray();
			}

			die(json_encode($sessions));
		break;

		case 'end_session':
			$session = new Session(strval($_POST['session_id']));
			if (!$session->valid() || !$session->isCloseable())
				die(json_encode(array('error' => 1)));

			$session->end();

			die(json_encode(array('success' => 1)));
		break;

		case 'set_gender':
			$result = Context::get()->getCurrentUser()->edit()->setGender(intval($_POST['gender']));
			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));
		break;

		case 'toggle_new_design':
			$themingSettings = $context->getCurrentUser()->getSettings()->getSettingsGroup('theming');

			$result = intval($themingSettings->useNewDesign(!$themingSettings->isNewDesignUsed())->isNewDesignUsed());

			die(json_encode(array('success' => $result)));
		break;

		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>