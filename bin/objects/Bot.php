<?php

namespace unt\objects;

use unt\platform\DataBaseManager;

/**
 * Bot entity object
 */

class Bot extends Entity 
{
    ////////////////////////////
    const ENTITY_TYPE = 'bot';
    ////////////////////////////

	private ?Settings $settings;

	private ?string $screenName = NULL;

	private string $name;
	private int $owner_id;
	private ?Photo $photo;

	function __construct (int $bot_id)
	{
        parent::__construct($bot_id);

		if ($bot_id === 0) return;

		$res = $this->currentConnection->prepare("SELECT id, name, owner_id, screen_name, photo_path, settings, is_verified, is_banned FROM bots.info WHERE id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$bot_id]))
		{
			$user_info = $res->fetch(\PDO::FETCH_ASSOC);
			if ($user_info)
			{
				$this->isValid  = true;

				$this->id = (int) $user_info['id'];
                $this->name = (string) $user_info['name'];
                $this->settings = new \unt\objects\Settings($this, $user_info);

				if ($user_info['screen_name'] !== "")
					$this->screenName = (string) $user_info['screen_name'];

				$this->isBanned = (int) $user_info['is_banned'];
				$this->isVerified = (int) $user_info['is_verified'];

				$this->owner_id = intval($user_info->owner_id);

				if ($user_info['photo_path'] !== "")
				{
					$photo = (new \unt\parsers\AttachmentsParser())->resolveFromQuery($user_info['photo_path']);
					if ($photo && $photo->getType() === Photo::ATTACHMENT_TYPE)
						$this->photo = $photo;
				}
			}
		}
	}

	public function canInviteToChat (): bool
	{
		if (!$this->valid()) return false;

		if (intval($_SESSION['user_id']) === $this->getOwnerId()) return true;

		$invitation = $this->getSettings()->getSettingsGroup(Settings::PRIVACY_GROUP)->getGroupValue('can_invite_to_chats');
		if (!$invitation || $invitation === 1) return true;

		return false;
	}

	public function getCurrentPhoto (): ?Photo
    {
		return $this->photo;
	}

	public function getScreenName (): ?string
    {
		return $this->screenName;
	}

	public function setScreenName (string $newScreenName): Bot
	{
		$this->screenName = $newScreenName;
		return $this;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getName (): string
	{
		return $this->name;
	}

	public function getType (): string
	{
		return self::ENTITY_TYPE;
	}

	public function setName (string $name): Bot
	{
		$this->name = $name;

		return $this;
	}

	public function setPhoto (?Photo $photo = NULL): Bot
	{
        if ($photo && !$photo->valid()) return $this;

		$this->photo = $photo;

		return $this;
	}

	public function apply (): bool
	{
		$new_name = trim($this->name);

		if (is_empty($new_name) || strlen($new_name) > 64) return false;
		if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-@$*#!%\d ]/ui", $new_name)) return false;

		$res = $this->currentConnection->prepare("UPDATE bots.info SET name = ? WHERE id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$new_name, $this->getId()]))
		{
			if ($this->photo && !$this->photo->valid()) return false;

			$query = $this->photo->getQuery();
			$res = DataBaseManager::getConnection()->prepare("UPDATE bots.info SET photo_path = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;");

			if ($res->execute([$query, $this->getId()])) return true;
		}

		return false;
	}

	public function getRelationsState (int $send_id = 0): int
	{
		$res = $this->currentConnection->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1");
		if ($res->execute([$send_id, $this->getId()]))
		{
			return intval($res->fetch(\PDO::FETCH_ASSOC)["state"]);
		}

		return 0;
	}

	public function toArray ($fields = ''): array
	{
		$result = [
			'bot_id'    => $this->getId(),
			'name'      => $this->getName(),
			'owner_id'  => $this->getOwnerId(),
			'gender'    => 1
		];

		$photo = $this->getCurrentPhoto();

		$screen_name = $this->getScreenName();
		if ($screen_name)
			$result['screen_name'] = $screen_name;

		if (!$this->isBanned())
		{
			if ($this->isVerified())
				$result['is_verified'] = true;

			if ($photo)
			{
				$result['photo_url'] = $photo->getLink();
			} else 
			{
				$result['photo_url'] = 'https://dev.yunnet.ru/images/default.png';
			}
		} else
		{
			$result['photo_url'] = 'https://dev.yunnet.ru/images/default.png';
			$result['is_banned'] = true;
		}

		$currentFields = [
			"can_write_messages", "can_write_on_wall", "can_invite_to_chat", "main_photo_as_object", "bot_can_write_messages"
		];

		$resultedFields = [];

		if ($fields === "*") $resultedFields = $currentFields;

		$workFields = substr($fields, 0, 1024);
		$tempArray  = explode(',', $workFields);

		foreach ($tempArray as $key => $value) 
		{
			if (in_array($value, $currentFields))
			{
				if (!in_array($value, $resultedFields)) $resultedFields[] = strval($value);
			}
		}

		if (in_array("can_write_messages", $resultedFields)) 
		{
			$dialog = new Dialog('b' . $this->getId());

			$result['can_write_messages'] = $dialog->canWrite();
		}
		if (in_array("can_write_on_wall", $resultedFields))
		{
			$result['can_write_on_wall'] = false;
		}
		if (in_array("can_invite_to_chat", $resultedFields))
		{
			$result['can_invite_to_chat'] = false;
        }
		if (in_array('main_photo_as_object', $resultedFields))
		{
			if ($this->getCurrentPhoto() !== NULL)
				$result['photo_object'] = $this->getCurrentPhoto()->toArray();
		}
		if (in_array("bot_can_write_messages", $resultedFields))
		{
			$result['bot_can_write_messages'] = false; //is_chat_allowed($this->currentConnection, intval($_SESSION['user_id']), $this->getId());
		}

		if (!$this->valid())
		{
			$result = [
				'name' => 'Deleted bot',
				'is_deleted' => true
			];
		}

		$result['account_type'] = $this->getType();

		return $result;
	}

	public function getSettings (): Settings
	{
		return $this->settings;
	}

	public function inBlacklist (): bool
	{
		return false;
	}

	public function isFriends (): int
	{
		return 0;
	}

	public function getAccountType (): int
	{
		return 0;
	}

	/////////////////////////////////////////
	public static function getList (): array
	{
		$result = [];

		$connection = DataBaseManager::getConnection();

		$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT id FROM bots.info WHERE owner_id = ? AND is_deleted = 0 LIMIT 30");
		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($data as $info) {
				$bot_id = intval($info['id']);

				$bot = new Bot($bot_id);
				if ($bot->valid())
					$result[] = $bot;
			}
		}

		return $result;
	}

	public static function create (string $name, ?Photo $photo = NULL): ?Bot
	{
		if (count(self::getList()) >= 30) return NULL;

		$bot_name = trim($name);

		if (is_empty($bot_name) || strlen($bot_name) > 64) return NULL;

		// only allowed letters and digits, space, and some symbols.
		if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-@$*#!%\d ]/ui", $bot_name)) return NULL;

		$settings = [
			"privacy" => [
				"can_write_messages"  => 0,
				"can_write_on_wall"   => 2,
				"can_invite_to_chats" => 1,
				"can_comment_posts"   => 0
			]
		];

		$res = DataBaseManager::getConnection()->prepare("INSERT INTO bots.info (name, owner_id, creation_time, settings) VALUES (?, ?, ?, ?)");

		if ($res->execute([$bot_name, intval($_SESSION['user_id']), time(), json_encode($settings)]))
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID()");

			if ($res->execute())
			{
				$bot_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$bot = new Bot($bot_id);
				if ($bot->valid())
					return $bot;
			}
		}

		return NULL;
	}
}

?>
