<?php

/**
 * Main cache class
 * May contain users, and other.
*/

class Cache 
{
	private $currentCacheServer = null;
	private $currentCacheName   = null;

	// load cache server. May be different.
	function __construct ($cacheName = "default")
	{
		$this->currentCacheServer = new Memcached();
		$this->currentCacheServer->addServer(Project::CACHE_IP, Project::CACHE_PORT);
		$this->currentCacheName = $cacheName;
	}

	public function getCacheName (): string
	{
		return $this->currentCacheName;
	}

	public function getItem ($name)
	{
		$result = $this->currentCacheServer->get($this->currentCacheName . '/' . $name);

		return $result;
	}

	public function putItem ($name, $value, $aliveTime = 86400): Cache
	{
		$item = $this->getItem($name);
		if ($item)
		{
			$this->currentCacheServer->replace($this->currentCacheName . '/' . $name, strval($value), $aliveTime);
		} else
		{
			$this->currentCacheServer->set($this->currentCacheName . '/' . $name, strval($value), $aliveTime);
		}

		return $this;
	}

	public function removeItem ($name): Cache
	{
		$this->currentCacheServer->delete($this->currentCacheName . '/' . $name);
		
		return $this;
	}

	/////////////////////////////////////
	public static function getCacheServer (): Memcached
	{
		$mem = new Memcached();
		$mem->addServer(Project::CACHE_IP, Project::CACHE_PORT);

		return $mem;
	}
}

?>