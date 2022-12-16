<?php

namespace unt\objects;

/**
 * Class with request data.
 * It contain different data from request to request/\
*/

class Request
{
	public array $data = [];

	public function __construct ()
	{
		parse_str(explode("?", $_SERVER["REQUEST_URI"])[1], $_REQUEST);
		$this->data = array_merge($_REQUEST, array_merge($_GET, $_POST));
	}

	public static function get ()
	{
		if (!isset($_SERVER['r']))
		{
			return ($_SERVER['r'] = new Request());
		}

		return $_SERVER['r'];
	}
}

?>