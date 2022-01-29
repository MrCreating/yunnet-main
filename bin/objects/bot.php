<?php

require_once __DIR__ . '/entity.php';
require_once __DIR__ . '/settings.php';

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

		$res = $this->currentConnection->cache("Bot_" . $bot_id)->prepare("SELECT id, name, owner_id, screen_name, photo_path, settings, is_verified, is_banned FROM bots.info WHERE id = ? AND is_deleted = 0 LIMIT 1");

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

	public function getRelationsState (int $send_id = 0): int
	{
		$res = $this->currentConnection->cache("Bot_relations_" . $send_id)->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1");
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
			if (!function_exists('get_uid_by_lid')) require __DIR__ . '/../functions/messages.php';

			$uid = intval(get_uid_by_lid($this->currentConnection, $this->getId(), $this->getType() === "bot", intval($_SESSION['user_id'])));

			$result['can_write_messages'] = can_write_to_chat($this->currentConnection, $uid, intval($_SESSION['user_id']), [
				"chat_id" => $this->getId(),
				"is_bot"  => $this->getType() === "bot"
			]);
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
}

?>