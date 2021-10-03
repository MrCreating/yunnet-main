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
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		if ($user->getType() === "user")
		{
			$settings = [
				'settings' => json_decode($user_info->settings),
				'themes'   => unserialize($user_info->themes)
			];

			$this->accountSettings  = new AccountSettingsGroup($connection, [
				'cookies'      => intval($user_info->cookies),
				'half_cookies' => intval($user_info->half_cookies),
				'is_closed'    => boolval($settings['settings']->closed_profile),
				'lang_id'      => strval($settings['settings']->lang)
			]);

			$this->pushSettings     = new PushSettingsGroup($connection, [
				'notifications' => boolval($settings['settings']->notifications->notifications),
				'sound'         => boolval($settings['settings']->notifications->sound)
			]);

			$this->privacySettings  = new PrivacySettingsGroup($connection, [
				'can_invite_to_chats' => intval($settings['settings']->privacy->can_invite_to_chats),
				'can_write_messages' => intval($settings['settings']->privacy->can_write_messages),
				'can_comment_posts' => intval($settings['settings']->privacy->can_comment_posts),
				'can_write_on_wall' => intval($settings['settings']->privacy->can_write_on_wall)
			]);

			$this->securitySettings = new SecuritySettingsGroup($connection, []);

			$this->themingSettings  = new ThemingSettingsGroup($connection, [
				'new_design' => boolval(intval($user_info->use_new_design)),
				'js_allowed' => boolval(intval($user_info->themes_allow_js)),
				'theme'      => strval($user_info->current_theme),
				'menu_items' => $settings['themes']['menu']
			]);
		} 
		else 
		{
			$settings = [
				'settings' => json_decode($user_info->settings)
			];

			$this->accountSettings  = new BotAccountSettingsGroup($connection, [
				'lang_id'      => strval($settings['settings']->lang)
			]);

			$this->privacySettings  = new PrivacySettingsGroup($connection, [
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