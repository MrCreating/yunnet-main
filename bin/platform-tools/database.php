<?php

/**
 * DataBase class. Represents PDO metbods for PHP.
*/

class DataBaseManager
{
	function __construct ()
	{
		throw new Exception('Unable to construct this');
	}

	//////////////////
	public static function getConnection (): PDO
	{
		if (isset($_SERVER['dbConnection']) && ($_SERVER['dbConnection'] instanceof PDO))
			return $_SERVER['dbConnection'];

		$host = boolval(intval(getenv('UNT_PRODUCTION'))) ? '212.109.219.153' : '212.109.219.153';
		$port = boolval(intval(getenv('UNT_PRODUCTION'))) ? 3306 : 3310;
		$pwd  = boolval(intval(getenv('UNT_PRODUCTION'))) ? Project::DB_PASSWORD : Project::DB_TEST_PASSWORD;

		$_SERVER['dbConnection'] = new PDO("mysql:host=".$host.";port=" . $port, Project::DB_USERNAME, $pwd, [
			PDO::ATTR_PERSISTENT => false
		]);

		return $_SERVER['dbConnection'];
	}
}
                           
?>