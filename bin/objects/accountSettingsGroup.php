<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Account settings
*/

class AccountSettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	private $balance;

	private bool   $closedProfile;
	private string $currentLangId;

	public function __construct (Entity $user, DataBaseManager $connection, array $params = [])
	{
		$this->currentEntity     = $user;
		
		$this->type              = "account";
		$this->currentConnection = $connection;

		$this->currentLangId = $params['lang_id'];
		$this->closedProfile = $params['is_closed'];

		$this->balance = new Data([
			'cookies'     => $params['cookies'],
			'halfCookies' => $params['half_cookies']
		]);
	}

	public function getBalance (): Data
	{
		return $this->balance;
	}

	public function isProfileClosed (): bool
	{
		return boolval($this->closedProfile);
	}

	public function setProfileClosed (bool $is_closed): AccountSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_account_is_closed = ? WHERE id = ? LIMIT 1;")->execute([intval(boolval($is_closed)), intval($_SESSION['user_id'])]))
		{
			$this->closedProfile = boolval($is_closed);
		}

		return $this;
	}

	public function getLanguageId (): string
	{
		return strval($this->currentLangId);
	}

	public function setLanguageId (string $newLangId): AccountSettingsGroup
	{
		$langs = ["ru", "en"];

		if (in_array($newLangId, $langs))
		{
			if ($this->currentConnection->prepare("UPDATE users.info SET settings_account_language = ? WHERE id = ? LIMIT 1;")->execute([$newLangId, intval($_SESSION['user_id'])]))
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
				'cookies'      => $this->getBalance()->cookies,
				'half_cookies' => $this->getBalance()->halfCookies
			],
			'is_closed' => intval($this->isProfileClosed()),
			'language'  => strval($this->getLanguageId())
		];
	}
}

?>