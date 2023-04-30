<?php

namespace unt\platform;

use PDO;

/**
 * DataBase class. Represents PDO metbods for PHP.
*/

class DataBaseManager
{
	protected ?PDO $dbClient;

	function __construct ()
	{
		$this->dbClient = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
            PDO::ATTR_PERSISTENT => false
        ]);
	}

	public function getClient (): PDO
	{
		return $this->dbClient;
	}

	public function prepare (string $query)
	{
		return $this->getClient()->prepare($query);
	}

    public function query (string $query, array $params = [], bool $one = false): ?array
    {
        $res = $this->dbClient->prepare($query);

        foreach ($params as $param)
        {
            $res->bindParam($param[0], $param[1], $param[2]);
        }

        if ($res->execute())
        {
            if ($one) {
                $result = $res->fetch(PDO::FETCH_ASSOC);

                if ($result === false)
                    return NULL;
                else return $result;
            }
            else return $res->fetchAll(PDO::FETCH_ASSOC);
        }

        return NULL;
    }

	//////////////////
	public static function getConnection (): DataBaseManager
	{
        static $db;
        if (!isset($db))
            $db = new self();

		return $db;
	}
}
                           
?>