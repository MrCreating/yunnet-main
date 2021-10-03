<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Security settings
*/

class SecuritySettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	public function __construct ($connection, array $params = [])
	{
		$this->currentConnection = $connection;
	}

	public function toArray (): array
	{
		return [];
	}
}
?>