<?php

/**
 * Class with request data.
 * It contain diffeent data from request to request/\
*/

class Request
{
	public $data = [];

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