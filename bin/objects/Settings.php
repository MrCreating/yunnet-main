<?php

namespace unt\objects;

/**
 * Settings class.
 * Needed for get, manage, and save settings
*/

class Settings extends BaseObject
{
    ///////////////////////////////
    const ACCOUNT_GROUP  = 'account';
    const PUSH_GROUP     = 'push';
    const PRIVACY_GROUP  = 'privacy';
    const SECURITY_GROUP = 'security';
    const THEMING_GROUP  = 'theming';
    ///////////////////////////////

	private SettingsGroup $accountSettings;
	private PushSettingsGroup $pushSettings;
	private PrivacySettingsGroup $privacySettings;
	private SecuritySettingsGroup $securitySettings;
    private ThemingSettingsGroup $themingSettings;

    function __construct (Entity $user, array $user_info)
	{
        parent::__construct();

		if ($user->getType() === User::ENTITY_TYPE && $user->valid())
		{
			$this->accountSettings = new AccountSettingsGroup($user, [
				'cookies'      => intval($user_info['cookies']),
				'half_cookies' => intval($user_info['half_cookies']),
				'is_closed'    => boolval(intval($user_info['settings_account_is_closed'])),
				'lang_id'      => strval($user_info['settings_account_language'])
			]);

			$this->pushSettings = new PushSettingsGroup($user, [
				'notifications' => boolval(intval($user_info['settings_push_notifications'])),
				'sound'         => boolval(intval($user_info['settings_push_sound']))
			]);

			$this->privacySettings = new PrivacySettingsGroup($user, [
				'can_invite_to_chats' => intval($user_info['settings_privacy_can_invite_to_chats']),
				'can_write_messages'  => intval($user_info['settings_privacy_can_write_messages']),
				'can_comment_posts'   => intval($user_info['settings_privacy_can_comment_posts']),
				'can_write_on_wall'   => intval($user_info['settings_privacy_can_write_on_wall'])
			]);

			$this->securitySettings = new SecuritySettingsGroup($user, []);

			$this->themingSettings = new ThemingSettingsGroup($user, [
				'new_design' => boolval(intval($user_info['settings_theming_new_design'])),
				'js_allowed' => boolval(intval($user_info['settings_theming_js_allowed'])),
				'theme'      => strval($user_info['settings_theming_current_theme']),
				'menu_items' => explode(',', $user_info['settings_theming_menu_items'])
			]);
		} 
		else if ($user->getType() === Bot::ENTITY_TYPE && $user->valid())
		{
			$settings = [
				'settings' => json_decode($user_info['settings'], true)
			];

			$this->accountSettings = new BotAccountSettingsGroup($user, [
				'lang_id' => strval($settings['settings']['lang'])
			]);

			$this->privacySettings = new PrivacySettingsGroup($user, [
				'can_invite_to_chats' => intval($settings['settings']['privacy']['can_invite_to_chats']),
				'can_write_messages'  => intval($settings['settings']['privacy']['can_write_messages']),
				'can_comment_posts'   => intval($settings['settings']['privacy']['can_comment_posts']),
				'can_write_on_wall'   => intval($settings['settings']['privacy']['can_write_on_wall'])
			]);
		}
	}

	function getSettingsGroup (string $type): ?SettingsGroup
	{
		switch ($type) {
            case self::ACCOUNT_GROUP:
				return $this->accountSettings;

            case self::PUSH_GROUP:
				return $this->pushSettings;

            case self::PRIVACY_GROUP:
				return $this->privacySettings;

            case self::SECURITY_GROUP:
				return $this->securitySettings;

            case self::THEMING_GROUP:
				return $this->themingSettings;

            default:
			break;
		}

		return NULL;
	}

	function toArray (): array
	{
		$result = [
			'account'  => $this->getSettingsGroup(self::ACCOUNT_GROUP)->toArray(),
			'privacy'  => $this->getSettingsGroup(self::PRIVACY_GROUP)->toArray(),
			'push'     => $this->getSettingsGroup(self::PUSH_GROUP)->toArray(),
			'security' => $this->getSettingsGroup(self::SECURITY_GROUP)->toArray(),
			'theming'  => $this->getSettingsGroup(self::THEMING_GROUP)->toArray()
		];

		foreach ($result as $key => $settingGroupArray) {
			if (!$settingGroupArray)
				unset($result[$key]);
		}

		return $result;
	}
}

?>