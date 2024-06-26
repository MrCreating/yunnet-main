<?php

namespace unt\objects;

use unt\platform\EventManager;

/**
 * Class for Push settings
*/

class PushSettingsGroup extends SettingsGroup
{
	private bool $notificationsEnabled;
	private bool $soundEnabled;

	public function __construct (Entity $user, array $params = [])
	{
		parent::__construct($user, Settings::PUSH_GROUP, $params);

		$this->notificationsEnabled = $params['notifications'];
		$this->soundEnabled         = $params['sound'];
	}

	public function isNotificationsEnabled (): bool
	{
		return boolval($this->notificationsEnabled);
	}

	public function setNotificationsEnabled (bool $enabled): PushSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_push_notifications = ? WHERE id = ? LIMIT 1;")->execute([intval($enabled), $this->entity->getId()]))
		{
			$this->notificationsEnabled = $enabled;

			$event = [
				'event' => 'interface_event',
				'data'  => [
					'sound' => intval($this->isSoundEnabled()),
					'notes' => intval($this->isNotificationsEnabled())
				]
			];

            EventManager::event([intval($_SESSION['user_id'])], $event);
		}

		return $this;
	}

	public function isSoundEnabled (): bool
	{
		return boolval($this->soundEnabled);
	}

	public function setSoundEnabled (bool $enabled): PushSettingsGroup
	{
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_push_sound = ? WHERE id = ? LIMIT 1;")->execute([intval($enabled), intval($this->entity->getId())]))
		{
			$this->soundEnabled = $enabled;

			$event = [
				'event' => 'interface_event',
				'data'  => [
					'sound' => intval($this->isSoundEnabled()),
					'notes' => intval($this->isNotificationsEnabled())
				]
			];

            EventManager::event([intval($_SESSION['user_id'])], $event);
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