<?php

namespace unt\objects;

/**
 * Class for Bot Account settings
*/

class BotAccountSettingsGroup extends SettingsGroup
{
    private string $currentLangId;

	public function __construct (Entity $user, array $params = [])
	{
        parent::__construct($user, Settings::ACCOUNT_GROUP, $params);

		$this->currentLangId = $params['lang_id'];
	}

	public function getLanguageId (): string
	{
		return strval($this->currentLangId);
	}

	public function setLanguageId (string $newLangId): SettingsGroup
	{
        $this->currentLangId = $newLangId;
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