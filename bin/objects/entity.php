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
}

?>