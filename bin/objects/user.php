<?php

require_once __DIR__ . '/entity.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/userInfoEditor.php';
require_once __DIR__ . '/dialog.php';
require_once __DIR__ . '/../functions/notifications.php';
require_once __DIR__ . '/../platform-tools/name_worker.php';

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

	private $accountType = NULL;

	private $newDesignAllowed = NULL;

	private $photo  = NULL;
	private $online = NULL;
	private $email  = NULL;

	function __construct (int $user_id)
	{
		if ($user_id === 0) return;

		$this->currentConnection = DataBaseManager::getConnection();

		$res = $this->currentConnection->cache('User_' . $user_id)->prepare("SELECT id, type, first_name, last_name, email, status, is_banned, is_verified, is_online, online_hidden, userlevel, photo_path, screen_name, cookies, half_cookies, gender, settings_account_language, settings_account_is_closed, settings_privacy_can_write_messages, settings_privacy_can_write_on_wall, settings_privacy_can_comment_posts, settings_privacy_can_invite_to_chats, settings_push_notifications, settings_push_sound, settings_theming_js_allowed, settings_theming_new_design, settings_theming_current_theme, settings_theming_menu_items FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$user_id]))
		{
			$user_info = $res->fetch(PDO::FETCH_ASSOC);
			if ($user_info)
			{
				$user_info = new Data($user_info);

				$this->isValid = true;
				$this->settings = new Settings($this, $user_info);

				$this->id = intval($user_info->id);
				$this->accessLevel = intval($user_info->userlevel);
				$this->accountType = intval($user_info->type);

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
	}

	public function isFriends (): bool
	{
		$user_id = intval($_SESSION['user_id']);

		if ($this->getId() === $user_id) return false;

		$res = $this->currentConnection->cache('User_relations_' . $user_id . '_' . $this->getId())->prepare("SELECT state FROM users.relationships WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;");
		if ($res->execute([strval($this->getId()), strval($user_id), strval($user_id), strval($this->getId())]))
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

		$res = $this->currentConnection->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
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

		$res = $this->currentConnection->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([strval($this->getId()), strval($user_id)]))
		{
			$state = intval($res->fetch(PDO::FETCH_ASSOC)["state"]);
			
			if ($state === -1) return true;
		}

		return false;
	}

	public function getBlacklist (int $offset = 0, int $count = 30): array
	{
		if ($count <= 0) $count = 1;
		if ($count >= 100) $count = 100;
		if ($offset < 0) $offset = 0;

		$res = $this->currentConnection->uncache()->prepare("SELECT DISTINCT added_id FROM users.blacklist WHERE state = -1 AND user_id = ? LIMIT ".intval($offset).", ".intval($count).";");

		$result = [];

		if ($res->execute([$this->getId()]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);

			foreach ($data as $info) {
				$user_id = intval($info['added_id']);

				$entity = Entity::findById($user_id);
				if ($entity)
					$result[] = $entity;
			}
		}

		return $result;
	}

	public function block (int $user_id): bool
	{
		if ($this->getId() === $user_id) return false;

		$res = $this->currentConnection->uncache()->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([$this->getId(), $user_id]))
		{
			$state = (int) $res->fetch(PDO::FETCH_ASSOC)["state"];
			if ($state === NULL)
			{
				return $this->currentConnection->uncache()->prepare("INSERT INTO users.blacklist (user_id, added_id, state) VALUES (?, ?, -1);")->execute([$this->getId(), $user_id]);
			}
			if (intval($state) === -1)
			{
				return $this->currentConnection->uncache()->prepare("UPDATE users.blacklist SET state = 0 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id]);
			} else
			{
				if (
					$this->currentConnection->uncache()->prepare("UPDATE users.relationships SET state = 0, is_hidden = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1;")->execute([$this->getId(), $user_id, $user_id, $this->getId()]) &&
					$this->currentConnection->uncache()->prepare("UPDATE users.blacklist SET state = -1 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id])
				)
				{
					return true;
				}
			}
		}

		return false;
	}

	public function canAccessClosed (): bool
	{
		if (!$this->valid()) return false;

		$user_id  = intval($_SESSION['user_id']);
		$check_id = $this->getId();

		if ($user_id === $check_id) return true;

		if ($this->getSettings()->getSettingsGroup('account')->isProfileClosed())
		{
			if ($user_id !== 0 && $this->isFriends()) return true;

			return false;
		}

		return true;
	}

	public function edit (): UserInfoEditor
	{
		return new UserInfoEditor($this);
	}

	public function getType (): string
	{
		return "user";
	}

	public function canInviteToChat (): bool
	{
		if (!$this->valid()) return false;
		if ($this->getId() === intval($_SESSION['user_id'])) return true;

		if (!$this->valid()) return false;
		if (!$this->isFriends()) return false;

		$invitation = $this->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_invite_to_chats');
		if (!$invitation || $invitation === 1) return true;

		return false;
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

			$account_type     = $this->getAccountType();

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
				$result['can_access_closed'] = $this->canAccessClosed();
			}
			if (in_array("is_me_blacklisted", $resultedFields))
			{
				$result['is_me_blacklisted'] = $this->inBlacklist();
			}
			if (in_array("is_blacklisted", $resultedFields))
			{
				$result['is_blacklisted'] = $this->isBlocked();
			}
			if (in_array("friend_state", $resultedFields))
			{
				if (!function_exists('get_friendship_state')) require __DIR__ . '/../functions/users.php';

				$result['friend_state'] = get_friendship_state($this->currentConnection, $this->getId(), intval($_SESSION['user_id']));
			}
			if (in_array("can_write_messages", $resultedFields)) 
			{
				$dialog = new Dialog($this->getId());

				$result['can_write_messages'] = $dialog->canWrite();
			}
			if (in_array("can_write_on_wall", $resultedFields))
			{
				if (!function_exists('can_write_posts')) require __DIR__ . '/../functions/wall.php';

				$objectId = $this->getType() === "bot" ? ($this->getId() * -1) : $this->getId();

				$result['can_write_on_wall'] = can_write_posts($this->currentConnection, intval($_SESSION['user_id']), $objectId);
			}
			if (in_array("can_invite_to_chat", $resultedFields))
			{
				$result['can_invite_to_chat'] = $this->canInviteToChat();
			}
			if (in_array('main_photo_as_object', $resultedFields))
			{
				if ($this->getCurrentPhoto() !== NULL)
					$result['photo_object'] = $this->getCurrentPhoto()->toArray();
			}
			if (in_array("name_cases", $resultedFields))
			{
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

			if ($account_type !== 0)
				$result['permissions_type'] = $account_type;
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

	public function getAccountType (): int
	{
		return $this->accountType;
	}

	////////////////////////////////////////////////////
	public static function findByEMAIL (string $email): ?User
	{
		$res = DataBaseManager::getConnection()->prepare('SELECT id FROM users.info WHERE email = ? LIMIT 1');

		if ($res->execute([$email]))
		{
			$id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);

			if ($id <= 0)
				return NULL;

			$user = new User($id);

			if ($user->valid())
				return $user;
		}

		return NULL;
	}

	public static function create (string $firstName, string $lastName, string $email, string $passwordHash, int $gender): ?User
	{
		$reg_time   = time();
		$connection = DataBaseManager::getConnection();

		$res = $connection->prepare("INSERT INTO users.info (first_name, last_name, password, email, gender, settings_account_language, registration_date, is_online) VALUES (:first_name, :last_name, :password, :email, :gender, :lang, :reg_time, :online_time);");

		$res->bindParam(":first_name",  $firstName,                        PDO::PARAM_STR);
		$res->bindParam(":last_name",   $lastName,                         PDO::PARAM_STR);
		$res->bindParam(":password",    $passwordHash,                     PDO::PARAM_STR);
		$res->bindParam(":email",       $email,                            PDO::PARAM_STR);
		$res->bindParam(":gender",      $gender,                           PDO::PARAM_INT);
		$res->bindParam(":lang",        Context::get()->getLanguage()->id, PDO::PARAM_STR);
		$res->bindParam(":reg_time",    $reg_time,                         PDO::PARAM_INT);
		$res->bindParam(":online_time", $reg_time,                         PDO::PARAM_INT); 

		if ($res->execute())
		{
			$res = $connection->prepare("SELECT LAST_INSERT_ID()");

			if ($res->execute())
			{
				$user_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$user = new User($user_id);
				if ($user->valid())
					return $user;
			}
		}

		return NULL;
	}

	public static function auth (string $email, string $password): ?User
	{
		$connection = DataBaseManager::getConnection();

		$res = $connection->prepare("SELECT id, password FROM users.info WHERE email = ? AND is_deleted = 0 LIMIT 1");
		if ($res->execute([$email]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);

			$user_id = intval($data['id']);
			$hash    = strval($data['password']);

			if (!$user_id || !password_verify($password, $hash)) return NULL;

			$entity = Entity::findById($user_id);
			if (!$entity) return NULL;

			create_notification($connection, $user_id, "account_login", [
				'ip'   => $_SERVER['REMOTE_ADDR'],
				'time' => time()
			]);

			$result = Session::start($user_id);
			if ($result && $result->setAsCurrent())
			{
				return $entity;
			}
		}

		return NULL;
	}
}

?>