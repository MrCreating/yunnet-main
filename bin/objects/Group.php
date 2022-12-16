<?php

namespace unt\objects;

abstract class Group extends BaseObject
{
    protected int $id;

	public function __construct ()
	{
        parent::__construct();
    }

	public function getId (): int
	{
		return $this->id;
	}
}

?>