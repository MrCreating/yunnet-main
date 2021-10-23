<?php

/**
 * DataBase class. Represents PDO metbods for PHP.
*/

// data class connect
require_once __DIR__ . "/data.php";

class DBRequestParam
{
	private $paramName;
	private $paramValue;
	private $paramType;

	function __construct ($paramName, $paramValue, $paramType)
	{
		$this->paramName = $paramName;
		$this->paramValue = $paramValue;
		$this->paramType = $paramType;
	}

	function getName ()
	{
		return $this->paramName;
	}

	function getValue ()
	{
		return $this->paramValue;
	}

	function getType ()
	{
		return $this->paramType;
	}
}

class DataBaseParams
{
	private $params = [];

	function __construct ($params = [])
	{
		foreach ($params as $index => $param) 
		{
			if ($param instanceof DBRequestParam)
				$this->params[] = $param;
		}
	}

	function getLength ()
	{
		return count($this->params);
	}

	function getParam ($index)
	{
		return $this->params[$index];
	}
}

class DataBaseConnection
{
	private $currentConnection = NULL;

	// main costructor. Checks for current connection or creates new.
	function __construct ()
	{
		if (!isset($_SERVER["dbConnection"]))
		{
			try
			{
				$this->currentConnection = new PDO("mysql:host=localhost;dbname=users", Project::DB_USERNAME, Project::DB_PASSWORD, [
					PDO::ATTR_PERSISTENT => true
				]);

				$_SERVER["dbConnection"] = $this->currentConnection;
			} catch (PDOException $e)
			{
				$this->currentConnection = NULL;
			}
		} else 
		{
			$this->currentConnection = $_SERVER["dbConnection"];
		}
	}

	public function execute ($query, $params)
	{
		if ($this->currentConnection === NULL)
			return false;

		if (!($params instanceof DataBaseParams))
			return false;

		$res = $this->currentConnection->prepare($query);

		for ($i = 0; $i < $params->getLength(); $i++)
		{
			$currentParam = $params->getParam($i);

			$name = $currentParam->getName();
			$val  = $currentParam->getValue();
			$type = $currentParam->getType();

			$res->bindParam($name, $val, $type);
		}

		if (!$res->execute())
			return false;

		$fetched = $res->fetchAll(PDO::FETCH_ASSOC);
		if (count($fetched) === 0)
			return false;

		$result = new DataBaseResult($fetched);

		return $result;
	}

	public function getPDOObject (): PDO
	{
		return $this->currentConnection;
	}

	function __destruct ()
	{
		$this->currentConnection = NULL;
	}
}

class DataBaseResult extends Data {};                            
?>