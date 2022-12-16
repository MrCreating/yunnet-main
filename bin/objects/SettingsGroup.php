<?php

namespace unt\objects;

/**
 * Settings group class definer.
 * Settings group is a class with setters for settings
*/
abstract class SettingsGroup extends BaseObject
{
	// type - is the group of settings
	protected string $type;

    // entity object
    protected Entity $entity;

	// this constructor all for all
	public function __construct (Entity $entity, string $type, array $params)
    {
        parent::__construct();

        $this->entity = $entity;
        $this->type   = $type;
    }

	// must be convertable to array
	abstract public function toArray (): array;
}

?>