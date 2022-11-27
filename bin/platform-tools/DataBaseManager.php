<?php

/**
 * DataBase class. Represents PDO metbods for PHP.
*/

class DataBaseManager
{
	protected $cacheClient = NULL;
	protected $dbClient    = NULL;

	private $cacheKey  = NULL;
	private $cacheTime = NULL;

	private $currentStatemenet = NULL;

	function __construct ()
	{
		if (!(isset($_SERVER['dbConnection']) && ($_SERVER['dbConnection'] instanceof PDO)))
		{
			$_SERVER['dbConnection'] = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
				PDO::ATTR_PERSISTENT => false
			]);
		}

		if (!(isset($_SERVER['cacheConnection']) && ($_SERVER['cacheConnection'] instanceof Cache)))
		{
			$_SERVER['cacheConnection'] = new Cache('queries');
		}

		$this->dbClient    = $_SERVER['dbConnection'];
		$this->cacheClient = $_SERVER['cacheConnection'];
	}

	public function getClient (): PDO
	{
		return $this->dbClient;
	}

	public function prepare (string $query): PDOStatement
	{
		return $this->getClient()->prepare($query);
	}

	public function cache (string $key, int $time = 86400): DataBaseManager
	{
		$this->cacheKey  = $key;
		$this->cacheTime = $time;

		return $this;
	}

	public function uncache (string $key = ''): DataBaseManager
	{
		$this->cacheKey  = NULL;
		$this->cacheTime = NULL;

		$this->cacheClient->removeItem($key);

		return $this;
	}

	//////////////////
	public static function getConnection (): DataBaseManager
	{
		return new DataBaseManager();
	}
}
                           
?>