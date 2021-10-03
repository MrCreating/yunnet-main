<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Privacy settings
*/

class PrivacySettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	private array $privacyValues;

	public function __construct ($connection, array $params = [])
	{
		$this->type              = "privacy";
		$this->currentConnection = $connection;

		$this->privacyValues     = $params;
	}

	public function getGroupValue (string $group): int
	{
		return intval($this->privacyValues[$group]);
	}

	public function setGroupValue (string $group, int $newValue): PrivacySettingsGroup
	{
		return $this;
	}

	public function toArray (): array
	{
		return [
			'can_write_messages'  => $this->getGroupValue('can_write_messages'),
			'can_write_on_wall'   => $this->getGroupValue('can_write_on_wall'),
			'can_comment_posts'   => $this->getGroupValue('can_comment_posts'),
			'can_invite_to_chats' => $this->getGroupValue('can_invite_to_chats')
		];
	}
}

?>