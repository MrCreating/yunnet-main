<?php

require_once __DIR__ . '/settingsGroup.php';
require_once __DIR__ . '/../event_manager.php';

/**
 * Class for Psuh settings
*/

class PushSettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;
	protected $eventEmitter      = NULL;

	private bool $notificationsEnabled;
	private bool $soundEnabled;

	public function __construct ($connection, array $params = [])
	{
		$this->type              = "push";
		$this->currentConnection = $connection;
		$this->eventEmitter      = new EventEmitter();

		$this->notificationsEnabled = $params['notifications'];
		$this->soundEnabled         = $params['sound'];
	}

	public function isNotificationsEnabled (): bool
	{
		return boolval($this->notificationsEnabled);
	}

	public function setNotificationsEnabled (bool $enabled): PushSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_push_notifications = ? WHERE id = ? LIMIT 1;")->execute([intval(boolval($enabled)), intval($_SESSION['user_id'])]))
		{
			$this->notificationsEnabled = boolval($enabled);
		}

		return $this;
	}

	public function isSoundEnabled (): bool
	{
		return boolval($this->soundEnabled);
	}

	public function setSoundEnabled (bool $enabled): PushSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_push_sound = ? WHERE id = ? LIMIT 1;")->execute([intval(boolval($enabled)), intval($_SESSION['user_id'])]))
		{
			$this->soundEnabled = boolval($enabled);
		}

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