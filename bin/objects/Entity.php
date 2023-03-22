<?php

namespace unt\objects;

/**
 * Main entity class
*/
abstract class Entity extends BaseObject
{
	protected int $id          = 0;
	protected int $accessLevel = 0;
	protected int $localRating = 0;

	protected bool $isBanned   = false;
	protected bool $isVerified = false;
	protected bool $isValid    = false;

	// only int constructor
	public function __construct (int $user_id)
    {
        parent::__construct();
    }

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
		$entity = $user_id > 0 ? new User($user_id) : new Bot($user_id * -1);

		return $entity->valid() ? $entity : NULL;
	}

	public static function runAs (int $user_id, callable $callback): bool
	{
		if (static::findById($user_id) === NULL) return false;

		$oldContext = Context::get();
		//$oldSession = Context::get()->getCurrentSession();

		$session = Session::start($user_id)->setAsCurrent();

		try 
		{
			$callback(Context::get());
		} catch (\Exception $e)
		{
		}

		$session->end();
		//$oldSession->setAsCurrent();
		$_SERVER['context'] = $oldContext;

		return true;
	}

	public static function findByScreenName (string $screen_name): ?Entity
	{
		$entity_id = 0;

		if (substr($screen_name, 0, 2) === "id")
		{
			$entity_id = (int) substr($screen_name, 2, strlen($screen_name));
		}
		if (substr($screen_name, 0, 3) === "bot")
		{
			$entity_id = ((int) substr($screen_name, 3, strlen($screen_name))) * -1;
		}

		if ($entity_id === 0)
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare('SELECT id FROM users.info WHERE screen_name = ? UNION SELECT IF(id != "", id * -1, NULL) FROM bots.info WHERE screen_name = ? LIMIT 1');
			if ($res->execute([$screen_name, $screen_name]))
			{
				$entity_id = intval($res->fetch(\PDO::FETCH_ASSOC)['id']);
			}
		}

		return $entity_id > 0 ? User::findById($entity_id) : Bot::findById($entity_id);
	}
}

?>