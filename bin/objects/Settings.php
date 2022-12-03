<?php

require_once __DIR__ . '/AccountSettingsGroup.php';
require_once __DIR__ . '/BotAccountSettingsGroup.php';
require_once __DIR__ . '/PushSettingsGroup.php';
require_once __DIR__ . '/PrivacySettingsGroup.php';
require_once __DIR__ . '/SecuritySettingsGroup.php';
require_once __DIR__ . '/ThemingSettingsGroup.php';

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
		$this->currentConnection = DataBaseManager::getConnection();

		if ($user->getType() === "user" && $user->valid())
		{
			$this->accountSettings  = new AccountSettingsGroup($user, $this->currentConnection, [
				'cookies'      => intval($user_info->cookies),
				'half_cookies' => intval($user_info->half_cookies),
				'is_closed'    => boolval(intval($user_info->settings_account_is_closed)),
				'lang_id'      => strval($user_info->settings_account_language)
			]);

			$this->pushSettings     = new PushSettingsGroup($user, $this->currentConnection, [
				'notifications' => boolval(intval($user_info->settings_push_notifications)),
				'sound'         => boolval(intval($user_info->settings_push_sound))
			]);

			$this->privacySettings  = new PrivacySettingsGroup($user, $this->currentConnection, [
				'can_invite_to_chats' => intval($user_info->settings_privacy_can_invite_to_chats),
				'can_write_messages' => intval($user_info->settings_privacy_can_write_messages),
				'can_comment_posts' => intval($user_info->settings_privacy_can_comment_posts),
				'can_write_on_wall' => intval($user_info->settings_privacy_can_write_on_wall)
			]);

			$this->securitySettings = new SecuritySettingsGroup($user, $this->currentConnection, []);

			$this->themingSettings  = new ThemingSettingsGroup($user, $this->currentConnection, [
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

			$this->accountSettings  = new BotAccountSettingsGroup($user, $this->currentConnection, [
				'lang_id'      => strval($settings['settings']->lang)
			]);

			$this->privacySettings  = new PrivacySettingsGroup($user, $this->currentConnection, [
				'can_invite_to_chats' => intval($settings['settings']->privacy->can_invite_to_chats),
				'can_write_messages' => intval($settings['settings']->privacy->can_write_messages),
				'can_comment_posts' => intval($settings['settings']->privacy->can_comment_posts),
				'can_write_on_wall' => intval($settings['settings']->privacy->can_write_on_wall)
			]);
		}
	}

	function getSettingsGroup (string $type): ?SettingsGroup
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
			if (!$settingGroupArray)
				unset($result[$key]);
		}

		return $result;
	}
}

?>