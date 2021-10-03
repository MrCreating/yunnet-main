<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Bot Account settings
*/

class BotAccountSettingsGroup extends SettingsGroup 
{
	protected $currentConnection = NULL;

	private string $currentLangId;

	public function __construct ($connection, array $params = [])
	{
		$this->type              = "account";
		$this->currentConnection = $connection;

		$this->currentLangId = $params['lang_id'];
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
			'language'  => strval($this->getLanguageId())
		];
	}
}

?>