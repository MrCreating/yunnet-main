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

	public function __construct ($connection, array $params = [])
	{
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
		return $this;
	}

	public function getLanguageId (): string
	{
		return strval($this->currentLangId);
	}

	public function setLanguageId (string $newLangId): AccountSettingsGroup
	{
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