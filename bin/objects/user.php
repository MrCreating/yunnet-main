<?php

require_once __DIR__ . '/entity.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/userInfoEditor.php';
require_once __DIR__ . '/../parsers/attachments.php';

/**
 * Default user entity.
 * Usable for accounts ant checking the settings
*/
class User extends Entity 
{
	private $currentConnection = NULL;
	private $settings          = NULL;

	private $firstName  = NULL;
	private $lastName   = NULL;
	private $status     = NULL;
	private $screenName = NULL;
	private $gender     = NULL;

	private $newDesignAllowed = NULL;

	private $photo  = NULL;
	private $online = NULL;
	private $email  = NULL;

	function __construct (int $user_id)
	{
		$this->currentConnection = new DataBaseConnection();

		if ($user_id === 0) return;

		$cache = new Cache("users");

		/*$user_info = $cache->getItem(strval($user_id));
		if (!$user_info)
		{*/
																/*themes,*/
																/*settings,*/ 
																/*use_new_design,*/
																/*current_theme,*/
																/*show_nav_button,*/ 
																/*themes_allow_js,*/

			$user_info = $this->currentConnection->execute("SELECT id, first_name, last_name, email, status, is_banned, is_verified, is_online, online_hidden, userlevel, photo_path, screen_name, cookies, half_cookies, gender, settings_account_language, settings_account_is_closed, settings_privacy_can_write_messages, settings_privacy_can_write_on_wall, settings_privacy_can_comment_posts, settings_privacy_can_invite_to_chats, settings_push_notifications, settings_push_sound, settings_theming_js_allowed, settings_theming_new_design, settings_theming_current_theme, settings_theming_menu_items FROM users.info WHERE id = :user_id AND is_deleted = 0 LIMIT 1;", new DataBaseParams([new DBRequestParam(":user_id", $user_id, PDO::PARAM_INT)]));

			$user_info = $user_info->{"0"};

			//$cache->putItem(strval($user_id), json_encode($user_info));
		//} else {
		//	$user_info = json_decode($user_info);
		//}

		if ($user_info)
		{
			$this->isValid = true;
			$this->settings = new Settings($this, $user_info);

			$this->id = intval($user_info->id);
			$this->accessLevel = intval($user_info->userlevel);

			$this->isBanned = boolval(intval($user_info->is_banned));
			$this->isVerified = boolval(intval($user_info->is_verified));

			$this->isOnlineHidden = boolval(intval($user_info->online_hidden));

			$this->status = $user_info->status;
			$this->email = strval($user_info->email);

			$this->firstName = strval($user_info->first_name);
			$this->lastName  = strval($user_info->last_name);
			$this->newDesignAllowed = boolval(intval($user_info->use_new_design));

			if ($user_info->screen_name !== "")
				$this->screenName = strval($user_info->screen_name);

			$this->gender = intval($user_info->gender);

			if ($user_info->photo_path !== "")
			{
				$photo = (new AttachmentsParser())->resolveFromQuery($user_info->photo_path);
				if ($photo)
					$this->photo = $photo;
			}

			$this->online = new Data([
				'lastOnlineTime' => intval($user_info->is_online),
				'isOnlineHidden' => boolval(intval($user_info->online_hidden)),
				'isOnline'       => boolval(intval($user_info->is_online) >= time())
			]);
		}
	}

	public function isFriends (): bool
	{
		$user_id = intval($_SESSION['user_id']);

		if ($this->getId() === $user_id) return false;

		$res = $this->currentConnection->getPDOObject()->prepare("SELECT state FROM users.relationships WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;");
		if ($res->execute([strval($owner_id), strval($user_id), strval($user_id), strval($owner_id)]))
		{
			$state = intval($res->fetch(PDO::FETCH_ASSOC)["state"]);

			if ($state === 2) return true;
		}

		return false;
	}

	public function isNewDesignUsed (): bool
	{
		return $this->newDesignAllowed;
	}

	public function isBlocked (): bool
	{
		$user_id = intval($_SESSION['user_id']);
		if ($user_id < 0) return false;

		if ($this->getId() === $user_id) return false;
		if ($this->getId() === 0 || $user_id === 0) return false;

		$res = $this->currentConnection->getPDOObject()->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([strval($user_id), strval($this->getId())]))
		{
			$state = intval($res->fetch(PDO::FETCH_ASSOC)["state"]);
			
			if ($state === 0) return false;
		}

		return true;
	}

	public function inBlacklist (): bool
	{
		$user_id = intval($_SESSION['user_id']);

		if ($this->getId() === $user_id) return false;
		if ($this->getId() === 0 || $user_id === 0) return false;

		if ($user_id < 0) return false;

		$res = $this->currentConnection->getPDOObject()->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([strval($this->getId()), strval($user_id)]))
		{
			$state = intval($res->fetch(PDO::FETCH_ASSOC)["state"]);
			
			if ($state === 0) return false;
		}

		return true;
	}

	public function block (int $user_id): bool
	{
		if ($this->getId() === $user_id) return false;

		$res = $this->currentConnection->getPDOObject()->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([$this->getId(), $user_id]))
		{
			$state = (int) $res->fetch(PDO::FETCH_ASSOC)["state"];
			if ($state === NULL)
			{
				return $this->currentConnection->getPDOObject()->prepare("INSERT INTO users.blacklist (user_id, added_id, state) VALUES (?, ?, -1);")->execute([$this->getId(), $user_id]);
			}
			if (intval($state) === -1)
			{
				return $this->currentConnection->getPDOObject()->prepare("UPDATE users.blacklist SET state = 0 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id]);
			} else
			{
				if (
					$this->currentConnection->getPDOObject()->prepare("UPDATE users.relationships SET state = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1;")->execute([$this->getId(), $user_id, $user_id, $this->getId()]) &&
					$this->currentConnection->getPDOObject()->prepare("UPDATE users.relationships SET is_hidden = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1;")->execute([$this->getId(), $user_id, $user_id, $this->getId()]) &&
					$this->currentConnection->getPDOObject()->prepare("UPDATE users.blacklist SET state = -1 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id])
				)
				{
					return true;
				}
			}
		}

		return false;
	}

	public function edit (): UserInfoEditor
	{
		return new UserInfoEditor($this);
	}

	public function getType (): string
	{
		return "user";
	}

	public function toArray ($fields = ''): array
	{
		$allFieldsList = [];

		$result = [
			'first_name' => $this->getFirstName(),
			'last_name'  => $this->getLastName(),
			'user_id'    => $this->getId(),
			'gender'     => $this->getGender(),
		];

		$screen_name = $this->getScreenName();
		if ($screen_name)
			$result['screen_name'] = $screen_name;

		if (!$this->isBanned())
		{
			$result['online'] = [];

			$photo  = $this->getCurrentPhoto();
			$online = $this->getOnline();

			$hidden_online    = $online->isOnlineHidden;
			$is_online        = $online->isOnline;
			$last_online_time = $online->lastOnlineTime;

			if ($online->isOnlineHidden)
			{
				$hidden_online    = true;
				$is_online        = false;
				$last_online_time = 0;
			}

			$result['online']['hidden_online']    = $hidden_online;
			$result['online']['is_online']        = $is_online;
			$result['online']['last_online_time'] = $last_online_time; 

			if ($photo)
			{
				$result['photo_url'] = $photo->getLink();
			} else 
			{
				$result['photo_url'] = 'https://dev.yunnet.ru/images/default.png';
			}

			if ($this->getStatus())
			{
				$result['status'] = $this->getStatus();
			}

			$currentFields = [
				"can_write_messages", "can_access_closed", "can_write_on_wall", "friend_state", "is_me_blacklisted", "is_blacklisted",
				"can_invite_to_chat", "main_photo_as_object", "name_cases"
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

			if (in_array("can_access_closed", $resultedFields)) 
			{
				if (!function_exists('can_access_closed')) require __DIR__ . '/../functions/users.php';

				$result['can_access_closed'] = can_access_closed($this->currentConnection->getPDOObject(), intval($_SESSION['user_id']), $this->getId());
			}
			if (in_array("is_me_blacklisted", $resultedFields))
			{
				if (!function_exists('in_blacklist')) require __DIR__ . '/../functions/users.php';

				$result['is_me_blacklisted'] = $this->inBlacklist();
			}
			if (in_array("is_blacklisted", $resultedFields))
			{
				if (!function_exists('in_blacklist')) require __DIR__ . '/../functions/users.php';

				$result['is_blacklisted'] = $this->isBlocked();
			}
			if (in_array("friend_state", $resultedFields))
			{
				if (!function_exists('get_friendship_state')) require __DIR__ . '/../functions/users.php';

				$result['friend_state'] = get_friendship_state($this->currentConnection->getPDOObject(), $this->getId(), intval($_SESSION['user_id']));
			}
			if (in_array("can_write_messages", $resultedFields)) 
			{
				if (!function_exists('get_uid_by_lid')) require __DIR__ . '/../functions/messages.php';

				$uid = intval(get_uid_by_lid($this->currentConnection->getPDOObject(), $this->getId(), $this->getType() === "bot", intval($_SESSION['user_id'])));

				$result['can_write_messages'] = can_write_to_chat($this->currentConnection->getPDOObject(), $uid, intval($_SESSION['user_id']), [
					"chat_id" => $this->getId(),
					"is_bot"  => $this->getType() === "bot"
				]);
			}
			if (in_array("can_write_on_wall", $resultedFields))
			{
				if (!function_exists('can_write_posts')) require __DIR__ . '/../functions/wall.php';

				$objectId = $this->getType() === "bot" ? ($this->getId() * -1) : $this->getId();

				$result['can_write_on_wall'] = can_write_posts($this->currentConnection->getPDOObject(), intval($_SESSION['user_id']), $objectId);
			}
			if (in_array("can_invite_to_chat", $resultedFields))
			{
				if (!function_exists('can_invite_to_chat')) require __DIR__ . '/../functions/users.php';

				$result['can_invite_to_chat'] = can_invite_to_chat($this->currentConnection->getPDOObject(), intval($_SESSION['user_id']), $this);
			}
			if (in_array('main_photo_as_object', $resultedFields))
			{
				if ($this->getCurrentPhoto() !== NULL)
					$result['photo_object'] = $this->getCurrentPhoto()->toArray();
			}
			if (in_array("name_cases", $resultedFields))
			{
				if (!class_exists('Name'))
					require __DIR__ . '/../name_worker.php';

				$name = new Name($this->getLastName(), $this->getFirstName(), '', $this->getGender() === 1 ? 'm' : 'f');

				$result['name_cases'] = [
					'first_name' => [
						'nom' => $name->work(1, 'num'),
						'gen' => $name->work(1, 'gen'),
						'dat' => $name->work(1, 'dat'),
						'acc' => $name->work(1, 'acc'),
						'ins' => $name->work(1, 'ins'),
						'pre' => $name->work(1, 'pre')
					],
					'last_name'  => [
						'nom' => $name->work(2, 'num'),
						'gen' => $name->work(2, 'gen'),
						'dat' => $name->work(2, 'dat'),
						'acc' => $name->work(2, 'acc'),
						'ins' => $name->work(2, 'ins'),
						'pre' => $name->work(2, 'pre')
					] 
				];
			}

			if ($this->isVerified())
				$result['is_verified'] = true;

			if ($this->getAccessLevel() > 0)
				$result['user_level'] = $this->getAccessLevel();
		} else
		{
			$result['photo_url'] = 'https://dev.yunnet.ru/images/default.png';
			$result['is_banned'] = true;
		}

		if (!$this->valid())
		{
			$result = [
				'first_name' => 'Deleted',
				'last_name'  => 'account',
				'is_deleted' => true
			];
		}

		$result['account_type'] = $this->getType();

		return $result;
	}

	public function getFirstName (): string
	{
		return $this->firstName;
	}

	public function getLastName (): string
	{
		return $this->lastName;
	}

	public function getStatus ()
	{
		return $this->status;
	}

	public function getGender (): int
	{
		return $this->gender;
	}

	public function getSettings (): Settings
	{
		return $this->settings;
	}

	public function getScreenName ()
	{
		return $this->screenName;
	}

	public function getCurrentPhoto ()
	{
		return $this->photo;
	}

	public function getOnline (): Data
	{
		return $this->online;
	}

	////////////////////////////////////////////////////
	public static function auth (string $login, string $password): ?Entity
	{
		
	}

	public static function authByToken (string $apiToken): ?Entity
	{

	}
}

?>