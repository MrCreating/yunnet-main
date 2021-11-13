<?php

/**
 * Main entity class
*/
abstract class Entity 
{
	protected $id          = 0;
	protected $accessLevel = 0;
	protected $localRating = 0;

	protected $isBanned   = false;
	protected $isVerified = false;
	protected $isValid    = false;

	// only int constructor
	abstract function __construct (int $user_id);

	// this method must return a string with entity type.
	abstract public function getType (): string;

	// to array conversion (must be implemented)
	abstract public function toArray (): array;

	public function getId (): int
	{
		return $this->id;
	}

	public function getAccessLevel (): int
	{
		return $this->accessLevel;
	}

	public function getLocalRating (): int
	{
		return $this->localRating;
	}

	public function isBanned (): bool
	{
		return $this->isBanned;
	}

	public function isVerified (): bool
	{
		return $this->isVerified;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	///////////////////////////////////////////////////
	public static function findById (int $user_id): ?Entity
	{
		$entity = $user_id < 0 ? new Bot($user_id * -1) : new User($user_id);

		if (!$entity->valid()) $entity = NULL;

		return $entity;
	}

	public static function runAs (int $user_id, callable $callback): bool
	{
		if (Entity::findById($user_id) === NULL) return false;

		$oldContext = Context::get();
		$oldSession = $oldContext->getCurrentSession();

		$session = Session::start($user_id)->setAsCurrent();

		$callback(Context::get());

		$session->end();
		$oldSession->setAsCurrent();
		$_SERVER['context'] = $oldContext;

		return true;
	}

	public static function findByScreenName (string $screen_name): ?Entity
	{
		$entity_id = 0;

		if (substr($screen_name, 0, 2) === "id")
		{
			$entity_id = intval(substr($screen_name, 2, strlen($screen_name)));
		}
		if (substr($screen_name, 0, 3) === "bot")
		{
			$entity_id = intval(substr($screen_name, 3, strlen($screen_name))) * -1;
		}

		if ($entity_id === 0)
		{
			$connection = $_SERVER['dbConnection'];
			if (!$connection)
				$connection = get_database_connection();

			$res = $connection->prepare('SELECT id FROM users.info WHERE screen_name = ? UNION SELECT IF(id != "", id * -1, NULL) FROM bots.info WHERE screen_name = ? LIMIT 1');

			if ($res->execute([$screen_name, $screen_name]))
			{
				$entity_id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);
			}
		}

		$entity = Entity::findById($entity_id);

		if ($entity) return $entity;

		return NULL;
	}
}

?>