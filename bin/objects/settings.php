<?php

if (!class_exists('Entity'))
	require __DIR__ . '/entities_new.php';

/**
 * Settings class.
 * Needed for get, manage, and save settings
*/

class Settings
{
	private $values = NULL;

	function __construct (string $settings)
	{
		$this->values = json_decode($settings);
	}

	function getValues ()
	{
		return $this->values;
	}

	function toArray (): array
	{
		return [];
	}
}

?>