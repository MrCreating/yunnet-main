<?php

/**
 * Main attachment class
*/

abstract class Attachment
{
	protected $isValid = false;

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	abstract public function toArray (): array;

	abstract public function getCredentials (): string;

	abstract public function getType (): string;
}

?>