<?php

/**
 * Permissions class
 * Represents the chat permissions
*/

class ChatPermissions
{
	public function __construct (Chat $chat)
	{}

	public function getValue (string $name): int
	{}

	public function setValue (string $name, int $newValue): bool
	{}
}

?>