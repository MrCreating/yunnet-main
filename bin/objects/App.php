<?php

namespace unt\objects;

/**
 * Declares App object
*/

class App extends BaseObject
{
	protected int $id;
	protected int $creation_time;

	private int $owner_id;
	private bool $isValid;
	private bool $directAuthEnabled;

	private string $title;
	private ?Photo $photo;
	private string $description;

	function __construct (int $app_id)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare("SELECT id, title, owner_id, description, photo_path, direct_auth, creation_time FROM apps.info WHERE id = ? AND is_deleted != 1 LIMIT 1");
		if ($res->execute([$app_id]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);

			if ($data)
			{
				$this->isValid           = true;
				$this->id                = (int) $data["id"];
				$this->title             = (string) $data["title"];
				$this->directAuthEnabled = (bool) $data['direct_auth'];
				$this->creation_time     = (int) $data['creation_time'];
				$this->description       = (string) $data["description"];
                $this->owner_id          = (int) $data['owner_id'];
				$this->photo             = $data['photo_path'] !== '' ? (new \unt\parsers\AttachmentsParser())->resolveFromQuery($data['photo_path']) : NULL;
			}
		}
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function getId (): int
	{
		return $this->id;
	}

	public function getCreationTime (): int
	{
		return $this->creation_time;
	}

	public function getTokensList (int $count = 100, int $offset = 0): array
	{
		if ($count < 0)	$count = 0;
		if ($count > 100) $count = 100;
		if ($offset < 0) $offset = 0;

		$result = [];

		if (!$this->valid())
			return $result;

		$res = $this->currentConnection->prepare("SELECT id FROM apps.tokens WHERE is_deleted != 1 AND owner_id = ? AND app_id = ? LIMIT ".$offset.",".$count.";");
		if ($res->execute([$_SESSION['user_id'], $this->getId()]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($data as $token_info)
			{
				$token = new Token($this, $token_info['id']);

				if ($token->valid())
					$result[] = $token;
			}
		}

		return $result;
	}

	/**
	 * Creates a token
	 * @param $permissions - array of the permissions for the token.
	 *
	 * Permissions:
	 * 1 - friends
	 * 2 - messages
	 * 3 - settings
	 * 4 - management (create tokens, sessions, etc)
	 * @return Token instamce or NULL if creation has failed.
	 *
	 * Parameters:
	 */
	public function createToken (array $permissions = [1, 2, 3, 4]): ?Token
    {
		if (!$this->valid()) return NULL;

		$token = substr(str_shuffle('abcdefghij'.rand(100000, 999999).'klmnopqrstuvwxyz'.rand(100000, 999999).'abcdefghijklmnopqrstuvwxyz'.rand(100000, 999999).'abcdefghijklmnopqrstuvwxyz'.rand(1000, 9999999)), 0, 75);
	
		$res = $this->currentConnection->prepare("INSERT INTO apps.tokens (app_id, owner_id, token, permissions) VALUES (?, ?, ?, ?)");

		if ($res->execute([$this->getId(), $_SESSION['user_id'], $token, implode('', $permissions)]))
		{
			// if OK - resolves an id and return a token with id.
			$res = $this->currentConnection->prepare("SELECT LAST_INSERT_ID() AS id");
			if ($res->execute())
			{
                $token_id = intval($res->fetch(\PDO::FETCH_ASSOC)["id"]);

				$token = new Token($this, $token_id);

				if (!$token->valid()) return NULL;

				if ($token->setPermissions($permissions)->apply())
                    return $token;
			}
		}

		return NULL;
	}

	public function isDirectAuthAllowed (): bool
	{
		return $this->directAuthEnabled;
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

	public function setPhoto (?Photo $photo = NULL): App
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
		$current_user_id  = $_SESSION['user_id'];
		$current_owner_id = $this->getOwnerId();

		if ($current_user_id !== $current_owner_id)
			return false;

		$new_title  = $this->getTitle();
		$current_id = $this->getId();

		if (\unt\functions\is_empty($new_title) || strlen($new_title) > 32)
			return false;

		$res = $this->currentConnection->prepare("UPDATE apps.info SET title = ? WHERE id = ? LIMIT 1");
		if ($res->execute([$new_title, $current_id]))
		{
			$photo = $this->getPhoto();
			if (!$photo)
			{
				$res = $this->currentConnection->prepare("UPDATE apps.info SET photo_path = NULL WHERE id = ? LIMIT 1");

                return $res->execute([$current_id]);
			} else
			{
				if (!$photo->valid())
					return false;

				$query = $photo->getQuery();
				if (!$query)
					return false;

				$res = $this->currentConnection->prepare("UPDATE apps.info SET photo_path = ? WHERE id = ? LIMIT 1");

				return $res->execute([$query, $current_id]);
			}
		}

		return false;
	}

	public function delete (): bool
	{
		$current_user_id  = $_SESSION['user_id'];
		$current_owner_id = $this->getOwnerId();

		if ($current_user_id !== $current_owner_id)
			return false;

		$this->isValid = false;

		return $this->currentConnection->prepare("UPDATE apps.info SET is_deleted = 1 WHERE id = ? LIMIT 1;")->execute([$this->getId()]);
	}

	//////////////////////////////////////////
	public static function create (string $title): ?App
	{
		$title    = trim($title);
		$owner_id = intval($_SESSION['user_id']);

		// current user id is not set.
		if ($owner_id == 0) return NULL;

		// checking title for empty and long-length.
		if (\unt\functions\is_empty($title) || strlen($title) > 64) return NULL;

		// inserting into DB and getting ID for app.
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("INSERT INTO apps.info (owner_id, title, creation_time) VALUES (?, ?, ?);");

		// if created.
		if ($res->execute([$owner_id, $title, time()]))
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			$res->execute();

			$app_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

			// return App instance.
			if ($app_id > 0)
			{
				$app = new App($app_id);
				if ($app->valid()) return $app;
			}
		}

		// not created app.
		return NULL;
	}
}

?>