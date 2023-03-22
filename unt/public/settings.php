<?php

use unt\objects\Context;
use unt\objects\Project;
use unt\objects\Request;
use unt\objects\Session;
use unt\platform\EventManager;

require_once __DIR__ . '/../../bin/functions/users.php';

/**
 * Here we will handle actions with settings.
*/

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
        case 'get_events_link':
            $link_key = EventManager::findByEntityId($_SESSION['user_id'])->createAuthKey();

            die(json_encode(array(
                'response' => [
                    'url' => Project::getEventServerDomain() . '/?key=' . $link_key,
                    'key' => $link_key,
                    'user_id' => intval($_SESSION['user_id'])
                ]
            )));

        case 'get_page':
            die(\unt\design\Template::get('settings')->show());

        case 'logout':
			header('Access-Control-Allow-Origin: ' . Project::getOrigin());
			header('Access-Control-Allow-Credentials: true');

			Context::get()->Logout();

			die(json_encode(array('success' => 1)));

        case 'change_language':
			$accountSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('account');

			die(json_encode(array('success' => intval($accountSettings->setLanguageId(Request::get()->data['lang'])->getLanguageId() === strtolower(Request::get()->data['lang'])))));

        case 'set_privacy_settings':
			$privacySettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('privacy');

			$groups = [
				1 => 'can_write_messages',
				2 => 'can_write_on_wall',
				3 => 'can_invite_to_chats',
				4 => 'can_comment_posts'
			];

			$group  = $groups[intval(Request::get()->data['group'])];
			$value  = intval(Request::get()->data['value']);
			$result = intval($privacySettings->setGroupValue($group, $value)->getGroupValue($group) === $value);

			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));

        case 'toggle_profile_state':
			$accountSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('account');

			die(json_encode(array('success' => intval($accountSettings->setProfileClosed(!$accountSettings->isProfileClosed())->isProfileClosed()))));

        case 'toggle_push_settings':
			$settingsGroup = strval(Request::get()->data['settings_group']);
			$groups = ['notifications', 'sound'];

			if (!in_array($settingsGroup, $groups))
				die(json_encode(array('error' => 1)));

			$new_value = boolval(intval(Request::get()->data['new_value']));

            $result = NULL;
			$pushSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('push');
			if ($settingsGroup === $groups[0])
				$result = $pushSettings->setNotificationsEnabled($new_value);
			if ($settingsGroup === $groups[1])
				$result = $pushSettings->setSoundEnabled($new_value);

			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response' => $new_value)));

        case 'get_blacklisted':
			$users  = Context::get()->getCurrentUser()->getBlacklist();
            $result = [];

			foreach ($users as $value) {
				$result[] = $value->toArray();
			}

			die(json_encode($result));

        case 'verify_password':
			$password = strval(Request::get()->data['password']);

			$result = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('security')->isPasswordCorrect($password);

			die(json_encode(array('state' => intval($result))));

        case 'change_password':
			$oldPassword = strval(Request::get()->data['old_password']);
			$newPassword = strval(Request::get()->data['new_password']);

			if (!Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('security')->isPasswordCorrect($oldPassword))
				die(json_encode(array('error' => 1)));

			$result = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('security')->setPassword($newPassword);

			if (!$result) die(json_encode(array('error' => 1)));

			$sessions = Session::getList();
			foreach ($sessions as $index => $session) {
				$session->end();
			}

			die(json_encode(array('state' => 1)));

        case 'get_accounts':
			die(json_encode(array('response' =>
                    Context::get()
                        ->getCurrentUser()
                        ->getSettings()
                        ->getSettingsGroup(\unt\objects\Settings::SERVICES_GROUP)
                        ->getServicesList()
            )));

        case 'update_menu_items':
			$items  = explode(',', strval(Request::get()->data['items']));

			$themingSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('theming');
			$success         = $themingSettings->setMenuItemIds($items);

			if (!$success)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));

        case 'toggle_js_state':
			$themingSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('theming');

			$result = intval($themingSettings->setJSAllowance(!$themingSettings->isJSAllowed())->isJSAllowed());

			die(json_encode(array('success' => $result)));

        case 'get_sessions_list':
			$sessions_list = Session::getList();

			$sessions = [];
			foreach ($sessions_list as $index => $session) 
			{
				$sessions[] = $session->toArray();
			}

			die(json_encode($sessions));

        case 'end_session':
			$session = new Session(strval(Request::get()->data['session_id']));
			if (!$session->valid() || !$session->isCloseable())
				die(json_encode(array('error' => 1)));

			$session->end();

			die(json_encode(array('success' => 1)));

        case 'set_gender':
			$result = Context::get()->getCurrentUser()->edit()->setGender(intval(Request::get()->data['gender']));
			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('success' => 1)));

        case 'toggle_new_design':
			$themingSettings = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('theming');

			$result = intval($themingSettings->useNewDesign(!$themingSettings->isNewDesignUsed())->isNewDesignUsed());

			die(json_encode(array('success' => $result)));

        default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>