<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Privacy settings
*/

class PrivacySettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	private array $privacyValues;

	public function __construct (Entity $user, DataBaseManager $connection, array $params = [])
	{
		$this->currentEntity     = $user;
		
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
		$groupsInfo = [
			'can_write_messages' => [
				'name'   => 'can_write_messages',
				'values' => [0, 1, 2]
			],
			'can_write_on_wall' => [
				'name'   => 'can_write_on_wall',
				'values' => [0, 1, 2]
			],
			'can_comment_posts' => [
				'name'   => 'can_comment_posts',
				'values' => [0, 1, 2]
			],
			'can_invite_to_chats' => [
				'name'   => 'can_invite_to_chats',
				'values' => [0, 1]
			]
		];

		if (!isset($groupsInfo[$group])) return $this;

		$currentGroup  = $groupsInfo[$group];
		$currentValues = $currentGroup['values'];

		if (!in_array($newValue, $currentValues)) return $this;

		if ($this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE users.info SET settings_privacy_".$currentGroup['name']." = ? WHERE id = ? LIMIT 1;")->execute([intval($newValue), intval($_SESSION['user_id'])]))
		{
			$this->privacyValues[$currentGroup['name']] = intval($newValue);

			return $this;
		}

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