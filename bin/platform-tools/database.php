<?php

require_once __DIR__ . '/database_query.php';

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
			$host = boolval(intval(getenv('UNT_PRODUCTION'))) ? 'mysql_prod' : '212.109.219.153';
			$port = boolval(intval(getenv('UNT_PRODUCTION'))) ? 3306 : 59876;
			$pwd  = boolval(intval(getenv('UNT_PRODUCTION'))) ? Project::DB_PASSWORD : Project::DB_TEST_PASSWORD;

			$_SERVER['dbConnection'] = new PDO("mysql:host=".$host.";port=" . $port, Project::DB_USERNAME, $pwd, [
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

	public function prepare (string $query): DataBaseStatemenet
	{
		return new DataBaseStatemenet($query, $this->dbClient, $this->cacheClient, $this->cacheKey, $this->cacheTime);
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