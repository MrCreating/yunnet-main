<?php

namespace unt\objects;

/**
 * Class for Privacy settings
*/

class PrivacySettingsGroup extends SettingsGroup
{
    ///////////////////////////////////
    const CAN_WRITE_MESSAGES  = 'can_write_messages';
    const CAN_WRITE_ON_WALL   = 'can_write_on_wall';
    const CAN_COMMENT_POSTS   = 'can_comment_posts';
    const CAN_INVITE_TO_CHATS = 'can_invite_to_chats';
    ///////////////////////////////////

	private array $privacyValues;

	public function __construct (Entity $user, array $params = [])
	{
        parent::__construct($user, Settings::PRIVACY_GROUP, $params);

		$this->privacyValues = $params;
	}

	public function getGroupValue (string $group): int
	{
		return intval($this->privacyValues[$group]);
	}

	public function setGroupValue (string $group, int $newValue): PrivacySettingsGroup
	{
		$groupsInfo = [
			self::CAN_WRITE_MESSAGES => [
				'name'   => self::CAN_WRITE_MESSAGES,
				'values' => [0, 1, 2]
			],
            self::CAN_WRITE_ON_WALL => [
				'name'   => self::CAN_WRITE_ON_WALL,
				'values' => [0, 1, 2]
			],
			self::CAN_COMMENT_POSTS => [
				'name'   => self::CAN_COMMENT_POSTS,
				'values' => [0, 1, 2]
			],
			self::CAN_INVITE_TO_CHATS => [
				'name'   => self::CAN_INVITE_TO_CHATS,
				'values' => [0, 1]
			]
		];

		if (!isset($groupsInfo[$group])) return $this;

		$currentGroup  = $groupsInfo[$group];
		$currentValues = $currentGroup['values'];

		if (!in_array($newValue, $currentValues)) return $this;

		if ($this->currentConnection->prepare("UPDATE users.info SET settings_privacy_".$currentGroup['name']." = ? WHERE id = ? LIMIT 1;")->execute([$newValue, intval($_SESSION['user_id'])]))
		{
			$this->privacyValues[$currentGroup['name']] = $newValue;

			return $this;
		}

		return $this;
	}

	public function toArray (): array
	{
		return [
			'can_write_messages'  => $this->getGroupValue(self::CAN_WRITE_MESSAGES),
			'can_write_on_wall'   => $this->getGroupValue(self::CAN_WRITE_ON_WALL),
			'can_comment_posts'   => $this->getGroupValue(self::CAN_COMMENT_POSTS),
			'can_invite_to_chats' => $this->getGroupValue(self::CAN_INVITE_TO_CHATS)
		];
	}
}

?>