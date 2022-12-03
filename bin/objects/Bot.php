<?php

require_once __DIR__ . '/Entity.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Dialog.php';

class Bot extends Entity 
{
	private $currentConnection = NULL;
	private $settings          = NULL;

	private $screenName = NULL;

	private $name     = NULL;
	private $owner_id = NULL;
	private $photo    = NULL;

	function __construct (int $bot_id)
	{
		$this->currentConnection = DataBaseManager::getConnection();

		if ($user_id === 0) return;

		$res = $this->currentConnection/*->cache("Bot_" . $bot_id)*/->prepare("SELECT id, name, owner_id, screen_name, photo_path, settings, is_verified, is_banned FROM bots.info WHERE id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$bot_id]))
		{
			$user_info = $res->fetch(PDO::FETCH_ASSOC);
			if ($user_info)
			{
				$user_info = new Data($user_info);

				$this->isValid  = true;
				$this->id = intval($user_info->id);

				if ($user_info->screen_name !== "")
					$this->screenName = strval($user_info->screen_name);

				$this->name = strval($user_info->name);

				$this->settings = new Settings($this, $user_info);

				$this->isBanned = boolval(intval($user_info->is_banned));
				$this->isVerified = boolval(intval($user_info->is_verified));

				$this->owner_id = intval($user_info->owner_id);

				if ($user_info->photo_path !== "")
				{
					$photo = (new attachmentsParser())->resolveFromQuery($user_info->photo_path);
					if ($photo)
						$this->photo = $photo;
				}
			}
		}
	}

	public function canInviteToChat (): bool
	{
		if (!$this->valid()) return false;

		if (intval($_SESSION['user_id']) === $this->getOwnerId()) return true;

		$invitation = $this->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_invite_to_chats');
		if (!$invitation || $invitation === 0 || $invitation === 1) return true;

		return false;
	}

	public function getCurrentPhoto ()
	{
		return $this->photo;
	}

	public function getScreenName ()
	{
		return $this->screenName;
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
		return "bot";
	}

	public function setName (string $name): Bot
	{
		$this->name = $name;

		return $this;
	}

	public function setPhoto (?Photo $photo = NULL): Bot
	{
		$this->photo = $photo;

		return $this;
	}

	public function apply (): bool
	{
		$new_name = trim($this->name);

		if (unt\functions\is_empty($new_name) || strlen($new_name) > 64) return false;
		if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-@$*#!%\d ]/ui", $new_name)) return false;

		$res = $this->currentConnection->prepare("UPDATE bots.info SET name = :new_name WHERE id = :id AND is_deleted = 0 LIMIT 1");

		$res->bindParam(":new_name", $new_name,      PDO::PARAM_STR);
		$res->bindParam(":id",       $this->getId(), PDO::PARAM_INT);

		if ($res->execute())
		{
			if ($this->photo && !$this->photo->valid()) return false;

			$query = $this->photo->getQuery();
			$res = DataBaseManager::getConnection()->prepare("UPDATE bots.info SET photo_path = :query WHERE id = :id AND is_deleted = 0 LIMIT 1;");

			$res->bindParam(":query", $query,         PDO::PARAM_STR);
			$res->bindParam(":id",    $this->getId(), PDO::PARAM_INT);

			if ($res->execute()) return true;
		}

		return false;
	}

	public function getRelationsState (int $send_id = 0): int
	{
		$res = $this->currentConnection/*->cache("Bot_relations_" . $send_id)*/->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1");
		if ($res->execute([$send_id, $this->getId()]))
		{
			return intval($res->fetch(PDO::FETCH_ASSOC)["state"]);
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
			if (!function_exists('can_write_posts')) require __DIR__ . '/../functions/wall.php';

			$objectId = $this->type === "bot" ? ($this->getId() * -1) : $this->getId();

			$result['can_write_on_wall'] = can_write_posts($this->currentConnection, intval($_SESSION['user_id']), $objectId);
		}
		if (in_array("can_invite_to_chat", $resultedFields))
		{
			if (!function_exists('can_invite_to_chat')) require __DIR__ . '/../functions/users.php';

			$result['can_invite_to_chat'] = can_invite_to_chat($this->currentConnection, intval($_SESSION['user_id']), $this);
		}
		if (in_array('main_photo_as_object', $resultedFields))
		{
			if ($this->getCurrentPhoto() !== NULL)
				$result['photo_object'] = $this->getCurrentPhoto()->toArray();
		}
		if (in_array("bot_can_write_messages", $resultedFields))
		{
			if (!function_exists('is_chat_allowed')) require __DIR__ . '/../functions/messages.php';

			$result['bot_can_write_messages'] = is_chat_allowed($this->currentConnection, intval($_SESSION['user_id']), $this->getId());
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

	/////////////////////////////////////////
	public static function getList (): array
	{
		$result = [];

		$connection = DataBaseManager::getConnection();

		$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT id FROM bots.info WHERE owner_id = ? AND is_deleted = 0 LIMIT 30");
		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);
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

		if (unt\functions\is_empty($bot_name) || strlen($bot_name) > 64) return NULL;

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

		$res = DataBaseManager::getConnection()->prepare("INSERT INTO bots.info (name, owner_id, creation_time, settings) VALUES (:name, :owner_id, :cr_time, :settings)");

		$res->bindParam(":name",     $bot_name,              PDO::PARAM_STR);
		$res->bindParam(":owner_id", $owner_id,              PDO::PARAM_INT);
		$res->bindParam(":cr_time",  time(),                 PDO::PARAM_INT);
		$res->bindParam(":settings", json_encode($settings), PDO::PARAM_STR);

		if ($res->execute())
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID()");

			if ($res->execute())
			{
				$bot_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$bot = new Bot($bot_id);
				if ($bot->valid())
					return $bot;
			}
		}

		return NULL;
	}
}

?>