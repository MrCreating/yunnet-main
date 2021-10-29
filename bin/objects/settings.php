<?php

require_once __DIR__ . '/accountSettingsGroup.php';
require_once __DIR__ . '/botAccountSettingsGroup.php';
require_once __DIR__ . '/pushSettingsGroup.php';
require_once __DIR__ . '/privacySettingsGroup.php';
require_once __DIR__ . '/securitySettingsGroup.php';
require_once __DIR__ . '/themingSettingsGroup.php';

/**
 * Settings class.
 * Needed for get, manage, and save settings
*/

class Settings
{
	private $accountSettings  = NULL;
	private $pushSettings     = NULL;
	private $privacySettings  = NULL;
	private $securitySettings = NULL;
	private $themingSettings  = NULL;

	private $currentConnection = NULL;

	function __construct (Entity $user, $user_info)
	{
		$connection = DataBaseManager::getConnection();
		$this->currentConnection = $connection;

		if ($user->getType() === "user" && $user->valid())
		{
			$this->accountSettings  = new AccountSettingsGroup($user, $connection, [
				'cookies'      => intval($user_info->cookies),
				'half_cookies' => intval($user_info->half_cookies),
				'is_closed'    => boolval(intval($user_info->settings_account_is_closed)),
				'lang_id'      => strval($user_info->settings_account_language)
			]);

			$this->pushSettings     = new PushSettingsGroup($user, $connection, [
				'notifications' => boolval(intval($user_info->settings_push_notifications)),
				'sound'         => boolval(intval($user_info->settings_push_sound))
			]);

			$this->privacySettings  = new PrivacySettingsGroup($user, $connection, [
				'can_invite_to_chats' => intval($user_info->settings_privacy_can_invite_to_chats),
				'can_write_messages' => intval($user_info->settings_privacy_can_write_messages),
				'can_comment_posts' => intval($user_info->settings_privacy_can_comment_posts),
				'can_write_on_wall' => intval($user_info->settings_privacy_can_write_on_wall)
			]);

			$this->securitySettings = new SecuritySettingsGroup($user, $connection, []);

			$this->themingSettings  = new ThemingSettingsGroup($user, $connection, [
				'new_design' => boolval(intval($user_info->settings_theming_new_design)),
				'js_allowed' => boolval(intval($user_info->settings_theming_js_allowed)),
				'theme'      => strval($user_info->settings_theming_current_theme),
				'menu_items' => explode(',', $user_info->settings_theming_menu_items)
			]);
		} 
		else 
		{
			$settings = [
				'settings' => json_decode($user_info->settings)
			];

			$this->accountSettings  = new BotAccountSettingsGroup($user, $connection, [
				'lang_id'      => strval($settings['settings']->lang)
			]);

			$this->privacySettings  = new PrivacySettingsGroup($user, $connection, [
				'can_invite_to_chats' => intval($settings['settings']->privacy->can_invite_to_chats),
				'can_write_messages' => intval($settings['settings']->privacy->can_write_messages),
				'can_comment_posts' => intval($settings['settings']->privacy->can_comment_posts),
				'can_write_on_wall' => intval($settings['settings']->privacy->can_write_on_wall)
			]);
		}
	}

	function getSettingsGroup (string $type): SettingsGroup
	{
		switch ($type) {
			case 'account':
				return $this->accountSettings;
			break;

			case 'push':
				return $this->pushSettings;
			break;

			case 'privacy':
				return $this->privacySettings;
			break;

			case 'security':
				return $this->securitySettings;
			break;

			case 'theming':
				return $this->themingSettings;
			break;

			default:	
			break;
		}

		return NULL;
	}

	function toArray (): array
	{
		$result = [
			'account'  => $this->getSettingsGroup('account')->toArray(),
			'privacy'  => $this->getSettingsGroup('privacy')->toArray(),
			'push'     => $this->getSettingsGroup('push')->toArray(),
			'security' => $this->getSettingsGroup('security')->toArray(),
			'theming'  => $this->getSettingsGroup('theming')->toArray()
		];

		foreach ($result as $key => $settingGroupArray) {
			if (!$result[$key])
				unset($result[$key]);
		}

		return $result;
	}
}

?>