<?php

require_once __DIR__ . '/SettingsGroup.php';

/**
 * Class for Bot Account settings
*/

class BotAccountSettingsGroup extends SettingsGroup 
{
	protected $currentConnection = NULL;

	private string $currentLangId;

	public function __construct (Entity $user, DataBaseManager $connection, array $params = [])
	{
		$this->currentEntity     = $user;
		
		$this->type              = "account";
		$this->currentConnection = DataBaseManager::getConnection();

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