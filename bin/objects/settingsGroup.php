<?php

/**
 * Settings group class definer.
 * Settings group is a class with setters for settings
*/
abstract class SettingsGroup
{
	// must have connection to DB
	protected $currentConnection;

	// type - is the group of settings
	protected string $type;

	// this constructor all for all
	abstract public function __construct (Entity $entity, PDO $connection, array $params);

	// must be convertable to array
	abstract public function toArray (): array;
}

?>