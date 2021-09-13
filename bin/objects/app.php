<?php

/**
 * Declares App object
*/

if (!class_exists('Entity'))
	require __DIR__ . '/entites.php';
if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/Attachment.php';
if (!class_exists('Token'))
	require __DIR__ . '/token.php';

class App
{
	protected $id            = 0;
	protected $creation_time = 0;

	private $owner             = NULL;
	private $isValid           = NULL;
	private $directAuthEnabled = NULL;

	private $title       = NULL;
	private $photo       = NULL;
	private $description = NULL;

	private $currentConnection = NULL;

	function __construct (int $app_id)
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;

		$res = $connection->prepare("SELECT id, title, owner_id, description, photo_path, direct_auth, creation_time FROM apps.info WHERE id = ? AND is_deleted != 1 LIMIT 1;");
		$res->execute([intval($app_id)]);
		$data = $res->fetch(PDO::FETCH_ASSOC);

		if ($data)
		{
			$this->isValid           = true;
			$this->id                = intval($data["id"]);
			$this->title             = strval($data["title"]);
			$this->directAuthEnabled = boolval($data['direct_auth']);
			$this->creation_time     = intval($data['creation_time']);
			$this->description       = strval($data["description"]);

			$this->owner = intval($data['owner_id']) > 0 ? new User(intval($data['owner_id'])) : new Bot(intval($data['owner_id']));
			$this->photo = $data['photo_path'] !== '' ? (new AttachmentsParser())->resolveFromQuery($data['photo_path']) : NULL;
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

	public function isDirectAuthAllowed (): bool
	{
		return boolval($this->directAuthEnabled);
	}

	public function getTitle (): string
	{
		return $this->title;
	}

	public function getOwner ()
	{
		return $this->owner;
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
			'owner_id'      => $this->getOwner()->getId(),
			'title'         => $this->getTitle(),
			'photo_url'     => ($this->getPhoto() ? $this->getPhoto()->getLink() : (DEFAULT_SCRIPTS_URL . '/images/default.png')),
			'description'   => $this->getDescription(),
			'creation_time' => $this->getCreationTime()
		];
	}

	public function apply (): bool
	{
		$current_user_id  = intval($_SESSION['user_id']);
		$current_owner_id = intval($this->getOwner()->getId());

		if ($current_user_id !== $current_owner_id)
			return false;

		$new_title  = $this->getTitle();
		$current_id = $this->getId();

		if (is_empty($new_title) || strlen($new_title) > 32)
			return false;

		$res = $this->currentConnection->prepare("UPDATE apps.info SET title = :new_title WHERE id = :id LIMIT 1;");
		$res->bindParam(":new_title", $new_title, PDO::PARAM_STR);
		$res->bindParam(":id",        $current_id,  PDO::PARAM_INT);

		if ($res->execute())
		{
			$photo = $this->getPhoto();
			if (!$photo)
			{
				$res = $this->currentConnection->prepare("UPDATE apps.info SET photo_path = NULL WHERE id = :id LIMIT 1;");
				$res->bindParam(":id", $current_id, PDO::PARAM_INT);
				$res->execute();
			} else
			{
				if (!$photo->valid())
					return false;

				$query = $photo->getQuery();
				if (!$query)
					return false;

				$res = $this->currentConnection->prepare("UPDATE apps.info SET photo_path = :new_path WHERE id = :id LIMIT 1;");
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
		$current_owner_id = intval($this->getOwner()->getId());

		if ($current_user_id !== $current_owner_id)
			return false;

		$this->isValid = false;

		return $this->currentConnection->prepare("UPDATE apps.info SET is_deleted = 1 WHERE id = ? LIMIT 1;")->execute([$this->getId()]);
	}

	//////////////////////////////////////////
	public static function create ($title): ?App
	{
		$title    = trim($title);
		$owner_id = intval($_SESSION['user_id']);

		// current user id is not set.
		if ($owner_id == 0) return NULL;

		// checking title for empty and long-length.
		if (is_empty($title) || strlen($title) > 64) return false;

		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		// inserting into DB and getting ID for app.
		$res = $connection->prepare("INSERT INTO apps.info (owner_id, title, creation_time) VALUES (:owner_id, :title, :creation_time);");

		$new_time = time();

		$res->bindParam(":owner_id",      $owner_id, PDO::PARAM_INT);
		$res->bindParam(":title",         $title,    PDO::PARAM_STR);
		$res->bindParam(":creation_time", $new_time, PDO::PARAM_INT);

		// if created.
		if ($res->execute())
		{
			$res = $connection->prepare("SELECT LAST_INSERT_ID();");
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