<?php

/**
 * This class has only setters
 * and used for edit ENTITY editing
*/

abstract class InfoEditor
{
	// current editable user
	protected $boundedEntity     = NULL;
	protected $currentConnection = NULL;

	// do not change this constructor...
	public function __construct (Entity $boundedEntity)
	{
		if (!$boundedEntity->valid()) return;

		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;
		$this->boundedEntity     = $boundedEntity;
	}

	public function getBoundEntity (): Entity
	{
		return $this->boundedEntity;
	}
}

?>