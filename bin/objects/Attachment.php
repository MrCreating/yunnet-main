<?php

namespace unt\objects;

/**
 * Main attachment class
*/

abstract class Attachment extends BaseObject
{
	protected bool $isValid = false;

	public function valid (): bool
	{
		return $this->isValid;
	}

	abstract public function toArray (): array;

	abstract public function getCredentials (): string;

	abstract public function getType (): string;
}

?>