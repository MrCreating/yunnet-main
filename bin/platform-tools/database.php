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

		$_SERVER['dbConnection'] = new PDO("mysql:host=212.109.219.153;port=3306", Project::DB_USERNAME, Project::DB_PASSWORD, [
			PDO::ATTR_PERSISTENT => false
		]);

		return $_SERVER['dbConnection'];
	}
}
                           
?>