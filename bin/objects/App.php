<?php

require_once __DIR__ . '/Token.php';

/**
 * Declares App object
*/

class App
{
	protected $id            = 0;
	protected $creation_time = 0;

	private $owner_id          = NULL;
	private $isValid           = NULL;
	private $directAuthEnabled = NULL;

	private $title       = NULL;
	private $photo       = NULL;
	private $description = NULL;

	private $currentConnection = NULL;

	function __construct (int $app_id)
	{
		$this->currentConnection = DataBaseManager::getConnection();

		$res = $this->currentConnection/*->cache("App_" . $app_id)*/->prepare("SELECT id, title, owner_id, description, photo_path, direct_auth, creation_time FROM apps.info WHERE id = ? AND is_deleted != 1 LIMIT 1");
		if ($res->execute([$app_id]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);

			if ($data)
			{
				$this->isValid           = true;
				$this->id                = intval($data["id"]);
				$this->title             = strval($data["title"]);
				$this->directAuthEnabled = boolval($data['direct_auth']);
				$this->creation_time     = intval($data['creation_time']);
				$this->description       = strval($data["description"]);

				$this->owner_id = intval($data['owner_id']);
				$this->photo    = $data['photo_path'] !== '' ? (new AttachmentsParser())->resolveFromQuery($data['photo_path']) : NULL;
			}
		}
	}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	public function getId (): int
	{
		return intval($this->id);
	}

	public function getCreationTime (): int
	{
		return intval($this->creation_time);
	}

	public function getTokens (int $count = 100, int $offset = 0): array
	{
		if ($count < 0)	$count = 0;
		if ($count > 100) $count = 100;
		if ($offset < 0) $offset = 0;

		$result = [];

		if (!$this->valid())
			return $result;

		$res = $this->currentConnection->prepare("SELECT id FROM apps.tokens WHERE is_deleted != 1 AND owner_id = ? AND app_id = ? LIMIT ".intval($offset).",".intval($count).";");
		if ($res->execute([intval($_SESSION['user_id']), $this->getId()]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);
			foreach ($data as $index => $token_info)
			{
				$token = new Token($this, intval($token_info['id']));

				if ($token->valid())
					$result[] = $token;
			}
		}

		return $result;
	}

	/**
	 * Creates a token
	 * @return Tokn instamce or NULL if creation has failed.
	 *
	 * Parameters:
	 * @param $permissions - array of the permissions for the token.
	 *
	 * Permissions:
	 * 1 - friends
	 * 2 - messages
	 * 3 - settings
	 * 4 - management (create tokens, sessions, etc)
	*/
	public function createToken (array $permissions = [1, 2, 3, 4]): ?Token
	{
		if (!$this->valid()) return NULL;

		$token = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'.rand(100000, 999999).'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz'.rand(1000, 9999999)), 0, 75);
	
		$res = $this->currentConnection->prepare("INSERT INTO apps.tokens (app_id, owner_id, token, permissions) VALUES (:app_id, :owner_id, :token, :permissions)");

		$res->bindParam(":app_id",      $this->getId(),       PDO::PARAM_INT);
		$res->bindParam(":owner_id",    $_SESSION['user_id'], PDO::PARAM_INT);
		$res->bindParam(":token",       $token,               PDO::PARAM_STR);
		$res->bindParam(":permissions", implode('', $permissions), PDO::PARAM_STR);

		if ($res->execute())
		{
			// if OK - resolves an id and return a token with id.
			$res = $this->currentConnection->prepare("SELECT LAST_INSERT_ID() AS id");
			if ($res->execute())
			{
                $token_id = intval($res->fetch(PDO::FETCH_ASSOC)["id"]);

				$token = new Token($this, $token_id);

				if (!$token->valid()) return NULL;

				if ($token->setPermissions($permissions)->apply()) return $token;
			}
		}

		return NULL;
	}

	public function isDirectAuthAllowed (): bool
	{
		return boolval($this->directAuthEnabled);
	}

	public function getTitle (): string
	{
		return $this->title;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function setTitle (string $title): App
	{
		$this->title = $title;

		return $this;
	}

	public function getDescription (): string
	{
		return $this->description;
	}

	public function getPhoto (): ?Photo
	{
		return $this->photo;
	}

	public function setPhoto (?Photo $photo): App
	{
		if ($photo && $photo->valid())
			$this->photo = $photo;
		if (!$photo)
			$this->photo = NULL;

		return $this;
	}

	public function toArray (): array
	{
		return [
			'id'            => $this->getId(),
			'owner_id'      => $this->getOwnerId(),
			'title'         => $this->getTitle(),
			'photo_url'     => ($this->getPhoto() ? $this->getPhoto()->getLink() : (Project::getDevDomain() . '/images/default.png')),
			'description'   => $this->getDescription(),
			'creation_time' => $this->getCreationTime()
		];
	}

	public function apply (): bool
	{
		$current_user_id  = intval($_SESSION['user_id']);
		$current_owner_id = intval($this->getOwnerId());

		if ($current_user_id !== $current_owner_id)
			return false;

		$new_title  = $this->getTitle();
		$current_id = $this->getId();

		if (unt\functions\is_empty($new_title) || strlen($new_title) > 32)
			return false;

		$res = $this->currentConnection->uncache("App_" . $This->getId())->prepare("UPDATE apps.info SET title = :new_title WHERE id = :id LIMIT 1");
		$res->bindParam(":new_title", $new_title, PDO::PARAM_STR);
		$res->bindParam(":id",        $current_id,  PDO::PARAM_INT);

		if ($res->execute())
		{
			$photo = $this->getPhoto();
			if (!$photo)
			{
				$res = $this->currentConnection->uncache("App_" . $This->getId())->prepare("UPDATE apps.info SET photo_path = NULL WHERE id = :id LIMIT 1");
				$res->bindParam(":id", $current_id, PDO::PARAM_INT);
				$res->execute();
			} else
			{
				if (!$photo->valid())
					return false;

				$query = $photo->getQuery();
				if (!$query)
					return false;

				$res = $this->currentConnection->uncache("App_" . $This->getId())->prepare("UPDATE apps.info SET photo_path = :new_path WHERE id = :id LIMIT 1");
				$res->bindParam(":new_path",  $query,      PDO::PARAM_STR);
				$res->bindParam(":id",        $current_id, PDO::PARAM_INT);
				$res->execute();
			}

			return true;
		}

		return false;
	}

	public function delete (): bool
	{
		$current_user_id  = intval($_SESSION['user_id']);
		$current_owner_id = intval($this->getOwnerId());

		if ($current_user_id !== $current_owner_id)
			return false;

		$this->isValid = false;

		return $this->currentConnection->uncache("App_" . $This->getId())->prepare("UPDATE apps.info SET is_deleted = 1 WHERE id = ? LIMIT 1;")->execute([$this->getId()]);
	}

	//////////////////////////////////////////
	public static function create ($title): ?App
	{
		$title    = trim($title);
		$owner_id = intval($_SESSION['user_id']);

		// current user id is not set.
		if ($owner_id == 0) return NULL;

		// checking title for empty and long-length.
		if (unt\functions\is_empty($title) || strlen($title) > 64) return false;

		// inserting into DB and getting ID for app.
		$res = DataBaseManager::getConnection()->prepare("INSERT INTO apps.info (owner_id, title, creation_time) VALUES (:owner_id, :title, :creation_time);");

		$new_time = time();

		$res->bindParam(":owner_id",      $owner_id, PDO::PARAM_INT);
		$res->bindParam(":title",         $title,    PDO::PARAM_STR);
		$res->bindParam(":creation_time", $new_time, PDO::PARAM_INT);

		// if created.
		if ($res->execute())
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			$res->execute();

			$app_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

			// return App instance.
			if ($app_id > 0)
			{
				$app = new App($app_id);
				if ($app->valid())
					return $app;
			}
		}

		// not created app.
		return NULL;
	}
}

?>