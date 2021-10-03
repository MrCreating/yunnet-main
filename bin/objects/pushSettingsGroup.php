<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Psuh settings
*/

class PushSettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	private bool $notificationsEnabled;
	private bool $soundEnabled;

	public function __construct ($connection, array $params = [])
	{
		$this->type              = "push";
		$this->currentConnection = $connection;

		$this->notificationsEnabled = $params['notifications'];
		$this->soundEnabled         = $params['sound'];
	}

	public function isNotificationsEnabled (): bool
	{
		return boolval($notificationsEnabled);
	}

	public function setNotificationsEnabled (bool $enabled): PushSettingsGroup
	{
		return $this;
	}

	public function isSoundEnabled (): bool
	{
		return boolval($soundEnabled);
	}

	public function setSoundEnabled (bool $enabled): PushSettingsGroup
	{
		return $this;
	}

	public function toArray (): array
	{
		return [
			'notifications' => intval($this->isNotificationsEnabled()),
			'sound'         => intval($this->isSoundEnabled()) 
		];
	}
}

?>