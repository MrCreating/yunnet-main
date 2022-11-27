<?php

abstract class Group
{
	public function __construct ()
	{}

	public function getId (): int
	{
		return intval($this->id);
	}
}

?>