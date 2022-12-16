<?php

namespace unt\objects;

/**
 * This class has only setters
 * and used for edit ENTITY editing
*/

abstract class InfoEditor extends BaseObject
{
	// current editable user
	protected Entity $boundedEntity;

	// do not change this constructor...
	public function __construct (Entity $boundedEntity)
	{
        parent::__construct();

		if (!$boundedEntity->valid()) return;

		$this->boundedEntity = $boundedEntity;
	}

	public function getBoundEntity (): Entity
	{
		return $this->boundedEntity;
	}
}

?>