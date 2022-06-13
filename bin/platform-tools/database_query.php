<?php

class DataBaseStatemenet
{
	private string $query = '';

	protected $cacheClient = NULL;
	protected $dbClient    = NULL;
	protected $sqlSession  = NULL;

	private $cacheKey   = NULL;
	private $cacheTime  = NULL;
	private $cacheValue = NULL;

	public function __construct (string $query, PDO $db, Cache $cache, ?string $cacheName = '', ?int $time = 0)
	{
		$this->dbClient    = $db;
		$this->cacheClient = $cache;
		$this->cacheKey    = $cacheName;
		$this->cacheTime   = $time;

		$this->sqlSession = $this->dbClient->prepare($query);
		$this->query      = $this->sqlSession->queryString;
	}

	public function execute (?array $params = NULL): bool
	{
		$cacheValue = false;

		if (!$cacheValue)
		{
			return $this->sqlSession->execute($params);
		} else
		{
			$this->cacheValue = $cacheValue;
			return true;
		}
	}

	public function bindParam (string $placeholder, $value, $param_type): bool
	{
		try 
		{
			return $this->sqlSession->bindParam($placeholder, $value, $param_type);
		} catch (Exception $e)
		{
			return false;
		}
	}

	public function fetch (int $pdoConst = PRO::FETCH_ASSOC)
	{
		if ($this->cacheValue)
			return $this->cacheValue;

		$data = $this->sqlSession->fetch($pdoConst);

		if ($data && $this->cacheKey !== NULL)
			$this->cacheClient->putItem($this->cacheKey, serialize($data));

		return $data;
	}

	public function fetchAll (int $pdoConst = PRO::FETCH_ASSOC): array
	{
		if ($this->cacheValue)
			return $this->cacheValue;

		$data = $this->sqlSession->fetchAll($pdoConst);
		if ($data && $this->cacheKey !== NULL)
			$this->cacheClient->putItem($this->cacheKey, serialize($data));

		return $data;
	}
}

?>