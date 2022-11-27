<?php

/**
 * Creadentials class
 * Used for result of attachment parsing
*/

class Credentials
{
	public $type       = '';
	public $owner_id   = 0;
	public $id         = 0;
	public $access_key = '';

	function __construct ($type = '', $owner_id = 0, $id = 0, $access_key = '')
	{
		$this->type       = strval($type);
		$this->owner_id   = intval($owner_id);
		$this->id         = intval($id);
		$this->access_key = strval($access_key);
	}
}

?>