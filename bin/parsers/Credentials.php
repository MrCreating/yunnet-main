<?php

namespace unt\parsers;

use unt\objects\BaseObject;

/**
 * Creadentials class
 * Used for result of attachment parsing
*/

class Credentials extends BaseObject
{
	public string $type       = '';
	public int $owner_id      = 0;
	public int $id            = 0;
	public string $access_key = '';

	function __construct ($type = '', $owner_id = 0, $id = 0, $access_key = '')
	{
        parent::__construct();

		$this->type       = strval($type);
		$this->owner_id   = intval($owner_id);
		$this->id         = intval($id);
		$this->access_key = strval($access_key);
	}
}

?>