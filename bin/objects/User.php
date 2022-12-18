<?php

namespace unt\objects;

use unt\parsers\AttachmentsParser;
use unt\parsers\Name;
use unt\platform\Data;
use unt\platform\DataBaseManager;

/**
 * Default user entity.
 * Usable for accounts ant checking the settings
*/
class User extends Entity 
{
    ////////////////////////////
    const ENTITY_TYPE = 'user';

    const FRIENDS_SECTION_MAIN        = 'friends';
    const FRIENDS_SECTION_SUBSCRIBERS = 'subscribers';
    const FRIENDS_SECTION_OUTCOMING   = 'outcoming';
    ////////////////////////////

	private Settings $settings;

	private string $firstName;
	private string $lastName;
	private ?string $status;
	private ?string $screenName;
	private ?int $gender;

	private ?int $accountType;

	private bool $newDesignAllowed;

	private ?Photo $photo = NULL;
	private Data $online;

	private string $email;

    private bool $isOnlineHidden = false;

    function __construct (int $user_id)
	{
        parent::__construct($user_id);

		if ($user_id === 0) return;

		$res = $this->currentConnection->prepare("SELECT id, type, first_name, last_name, email, status, is_banned, is_verified, is_online, online_hidden, userlevel, photo_path, screen_name, cookies, half_cookies, gender, settings_account_language, settings_account_is_closed, settings_privacy_can_write_messages, settings_privacy_can_write_on_wall, settings_privacy_can_comment_posts, settings_privacy_can_invite_to_chats, settings_push_notifications, settings_push_sound, settings_theming_js_allowed, settings_theming_new_design, settings_theming_current_theme, settings_theming_menu_items FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$user_id]))
		{
			$user_info = $res->fetch(\PDO::FETCH_ASSOC);
			if ($user_info)
			{
				$this->isValid = true;
				$this->settings = new Settings($this, $user_info);

				$this->id = intval($user_info['id']);
				$this->accessLevel = intval($user_info['userlevel']);
				$this->accountType = intval($user_info['type']);

				$this->isBanned = boolval(intval($user_info['is_banned']));
				$this->isVerified = boolval(intval($user_info['is_verified']));

				$this->isOnlineHidden = boolval(intval($user_info['online_hidden']));

				$this->status = $user_info['status'];
				$this->email = strval($user_info['email']);

				$this->firstName = strval($user_info['first_name']);
				$this->lastName  = strval($user_info['last_name']);
				$this->newDesignAllowed = boolval(intval($user_info['use_new_design']));

				if ($user_info->screen_name !== "")
					$this->screenName = strval($user_info['screen_name']);

				$this->gender = intval($user_info['gender']);

				if ($user_info->photo_path !== "")
				{
					$photo = (new AttachmentsParser())->resolveFromQuery($user_info['photo_path']);
					if ($photo)
						$this->photo = $photo;
				}

				$this->online = new Data([
					'lastOnlineTime' => intval($user_info['is_online']),
					'isOnlineHidden' => boolval(intval($user_info['online_hidden'])),
					'isOnline'       => intval($user_info['is_online']) >= time()
				]);
			}
		}
	}

	public function isFriends (): bool
	{
		$user_id = intval($_SESSION['user_id']);

		if ($this->getId() === $user_id) return false;
		if (!Context::get()->isLogged()) return false;

		$res = $this->currentConnection->prepare("SELECT state FROM users.relationships WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;");
		if ($res->execute([strval($this->getId()), strval($user_id), strval($user_id), strval($this->getId())]))
		{
			$state = intval($res->fetch(\PDO::FETCH_ASSOC)["state"]);

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
		if ($user_id <= 0) return false;

		if (!Context::get()->isLogged()) return false;

		if ($this->getId() === $user_id) return false;
		if ($this->getId() === 0) return false;

		$res = $this->currentConnection->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([strval($user_id), strval($this->getId())]))
		{
			$state = intval($res->fetch(\PDO::FETCH_ASSOC)["state"]);
			
			if ($state === 0) return false;
		}

		return true;
	}

	public function inBlacklist (): bool
	{
		$user_id = intval($_SESSION['user_id']);

		if (!Context::get()->isLogged()) return false;

		if ($this->getId() === $user_id) return false;
		if ($this->getId() === 0 || $user_id === 0) return false;

		if ($user_id < 0) return false;

		$res = $this->currentConnection->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([strval($this->getId()), strval($user_id)]))
		{
			$state = intval($res->fetch(\PDO::FETCH_ASSOC)["state"]);
			
			if ($state === -1) return true;
		}

		return false;
	}

	public function getBlacklist (int $offset = 0, int $count = 30): array
	{
		if ($count <= 0) $count = 1;
		if ($count >= 100) $count = 100;
		if ($offset < 0) $offset = 0;

		$res = $this->currentConnection->prepare("SELECT DISTINCT added_id FROM users.blacklist WHERE state = -1 AND user_id = ? LIMIT ". $offset .", ". $count .";");

		$result = [];

		if ($res->execute([$this->getId()]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);

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
		if (!Context::get()->isLogged()) return false;
		if ($this->getId() === $user_id) return false;

		$res = $this->currentConnection->prepare("SELECT state FROM users.blacklist WHERE user_id = ? AND added_id = ? LIMIT 1;");
		if ($res->execute([$this->getId(), $user_id]))
		{
			$state = $res->fetch(\PDO::FETCH_ASSOC)["state"];
			if ($state === NULL)
			{
				return $this->currentConnection->prepare("INSERT INTO users.blacklist (user_id, added_id, state) VALUES (?, ?, -1);")->execute([$this->getId(), $user_id]);
			}
			if ((int) $state === -1)
			{
				return $this->currentConnection->prepare("UPDATE users.blacklist SET state = 0 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id]);
			} else
			{
				if (
					$this->currentConnection->prepare("UPDATE users.relationships SET state = 0, is_hidden = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ? LIMIT 1;")->execute([$this->getId(), $user_id, $user_id, $this->getId()]) &&
					$this->currentConnection->prepare("UPDATE users.blacklist SET state = -1 WHERE user_id = ? AND added_id = ? LIMIT 1;")->execute([$this->getId(), $user_id])
				)
				{
					return true;
				}
			}
		}

		return false;
	}

    /**
     * Получает ленту новостей текущего юзера
     * @return array<Post>
     */
    public function getNewsList (): array
    {
        $result = [];

        $friends_list = array_merge([$this->getId()], $this->getFriendsList());;
        foreach ($friends_list as $friend_id)
        {
            $res = DataBaseManager::getConnection()->prepare('SELECT local_id FROM wall.posts WHERE to_id = ? AND owner_id = ? AND is_deleted = 0 ORDER BY time DESC LIMIT 5;');

            if ($res->execute([$friend_id, $friend_id]))
            {
                $data = $res->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($data as $post)
                {
                    $local_id = (int) $post['local_id'];

                    $post = Post::findById($friend_id, $local_id);

                    if ($post)
                        $result[] = $post;
                }
            }
        }

        usort($result, function ($a, $b) {
            return $a->getCreationTime() - $b->getCreationTime();
        });

        return array_reverse(array_map(function ($post) {
            return $post->toArray();
        }, $result));
    }

    public function getFriendsList (string $section = self::FRIENDS_SECTION_MAIN, bool $extended = false): array
    {
        $result = [];

        switch ($section)
        {
            case "subscribers":
                $res = DataBaseManager::getConnection()->prepare("SELECT user1, user2, state FROM users.relationships WHERE user2 = ? AND state = 1 AND user1 != user2 LIMIT 50;");
                $res->execute([$this->getId()]);
                break;
            case "outcoming":
                $res = DataBaseManager::getConnection()->prepare("SELECT user1, user2, state FROM users.relationships WHERE user1 = ? AND state = 1 AND user1 != user2 LIMIT 50;");
                $res->execute([$this->getId()]);
                break;
            default:
                $res = DataBaseManager::getConnection()->prepare("SELECT user1, user2, state FROM users.relationships WHERE (user1 = ? OR user2 = ?) AND state = 2 AND user1 != user2 LIMIT 50;");
                $res->execute([$this->getId(), $this->getId()]);
                break;
        }

        $identifiers = $res->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($identifiers as $userdata)
        {
            $user_current = intval($userdata["user1"]);
            if ($user_current === $this->getId())
                $user_current = intval($userdata["user2"]);

            if ($extended)
            {
                $user = new User($user_current);
                if (!$user->valid()) continue;

                $result[] = $user;
            }
            else
                $result[] = $user_current;
        }

        return $result;
    }

	public function canAccessClosed (): bool
	{
		if (!$this->valid()) return false;

		$user_id  = intval($_SESSION['user_id']);
		$check_id = $this->getId();

		if ($user_id === $check_id) return true;

		if ($this->getSettings()->getSettingsGroup(Settings::ACCOUNT_GROUP)->isProfileClosed())
		{
			if (!Context::get()->isLogged()) return false;

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
		return self::ENTITY_TYPE;
	}

	public function canInviteToChat (): bool
	{
		if (!Context::get()->isLogged()) return false;
		
		if (!$this->valid()) return false;
		if ($this->getId() === intval($_SESSION['user_id'])) return true;

        if (!$this->isFriends()) return false;

		$invitation = $this->getSettings()->getSettingsGroup(Settings::PRIVACY_GROUP)->getGroupValue('can_invite_to_chats');
		if (!$invitation || $invitation === 1) return true;

		return false;
	}

	public function toArray (string $fields = ''): array
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
				if (!function_exists('get_friendship_state'))
                    require_once __DIR__ . '/../functions/users.php';

				$result['friend_state'] = \unt\functions\users\get_friendship_state($this->currentConnection, $this->getId(), intval($_SESSION['user_id']));
			}
			if (in_array("can_write_messages", $resultedFields)) 
			{
				$result['can_write_messages'] = false;
			}
			if (in_array("can_write_on_wall", $resultedFields))
			{
				if (!function_exists('can_write_posts'))
                    require_once __DIR__ . '/../functions/wall.php';

				$objectId = $this->getType() === Bot::ENTITY_TYPE ? ($this->getId() * -1) : $this->getId();

				$result['can_write_on_wall'] = \unt\functions\wall\can_write_posts($this->currentConnection, intval($_SESSION['user_id']), $objectId);
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

	public function getScreenName (): string
    {
		return $this->screenName;
	}

	public function getCurrentPhoto (): ?Photo
    {
		return $this->photo;
	}

	public function getOnline (): Data
    {
		return $this->online;
	}

	public function getAccountType (): string
	{
		return $this->accountType;
	}

	////////////////////////////////////////////////////
	public static function findByEMAIL (string $email): ?User
	{
		$res = \unt\platform\DataBaseManager::getConnection()->prepare('SELECT id FROM users.info WHERE email = ? LIMIT 1');

		if ($res->execute([$email]))
		{
			$id = intval($res->fetch(\PDO::FETCH_ASSOC)['id']);

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
        $language_identifier = Context::get()->getLanguage()->id;
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("INSERT INTO users.info (first_name, last_name, password, email, gender, settings_account_language, registration_date, is_online) VALUES (:first_name, :last_name, :password, :email, :gender, :lang, :reg_time, :online_time);");

		$res->bindParam(":first_name",  $firstName,           \PDO::PARAM_STR);
		$res->bindParam(":last_name",   $lastName,            \PDO::PARAM_STR);
		$res->bindParam(":password",    $passwordHash,        \PDO::PARAM_STR);
		$res->bindParam(":email",       $email,               \PDO::PARAM_STR);
		$res->bindParam(":gender",      $gender,              \PDO::PARAM_INT);
		$res->bindParam(":lang",        $language_identifier, \PDO::PARAM_STR);
		$res->bindParam(":reg_time",    $reg_time,            \PDO::PARAM_INT);
		$res->bindParam(":online_time", $reg_time,            \PDO::PARAM_INT);

		if ($res->execute())
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID()");

			if ($res->execute())
			{
				$user_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$user = new User($user_id);
				if ($user->valid())
					return $user;
			}
		}

		return NULL;
	}

	public static function auth (string $email, string $password): ?User
	{
		$connection = \unt\platform\DataBaseManager::getConnection();

		$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT id, password FROM users.info WHERE email = ? AND is_deleted = 0 LIMIT 1");
		if ($res->execute([$email]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);

			$user_id = intval($data['id']);
			$hash    = strval($data['password']);

			if (!$user_id || !password_verify($password, $hash)) return NULL;

			$entity = User::findById($user_id);
			if (!$entity) return NULL;

			Notification::create($user_id, "account_login", [
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