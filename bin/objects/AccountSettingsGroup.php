<?php

namespace unt\objects;

/**
 * Class for Account settings
*/

class AccountSettingsGroup extends SettingsGroup
{
	private CookieManager $balance;

	private bool   $closedProfile;
	private string $currentLangId;

    public function __construct (Entity $user, array $params = [])
	{
        parent::__construct($user, Settings::ACCOUNT_GROUP, $params);

		$this->currentLangId = (string) $params['lang_id'];
		$this->closedProfile = (bool) (int) $params['is_closed'];

		$this->balance = new CookieManager($user, $params['cookies'], $params['half_cookies']);
	}

	public function getBalance (): CookieManager
	{
		return $this->balance;
	}

	public function isProfileClosed (): bool
	{
		return $this->closedProfile;
	}

	public function setProfileClosed (bool $is_closed): AccountSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_account_is_closed = ? WHERE id = ? LIMIT 1;")->execute([$is_closed, $_SESSION['user_id']]))
		{
			$this->closedProfile = $is_closed;
		}

		return $this;
	}

	public function getLanguageId (): string
	{
		return $this->currentLangId;
	}

	public function setLanguageId (string $newLangId): AccountSettingsGroup
	{
		$languages_list = ["ru", "en"];

		if (in_array($newLangId, $languages_list))
		{
			if ($this->currentConnection->prepare("UPDATE users.info SET settings_account_language = ? WHERE id = ? LIMIT 1;")->execute([$newLangId, $_SESSION['user_id']]))
			{
				$this->currentLangId = $newLangId;
			}
		}

		return $this;
	}

	public function toArray (): array
	{
		return [
			'balance' => [
				'cookies'      => $this->getBalance()->getCookiesCount(),
				'half_cookies' => $this->getBalance()->getBiteCookiesCount()
			],
			'is_closed' => (int) $this->isProfileClosed(),
			'language'  => $this->getLanguageId()
		];
	}
}

?>