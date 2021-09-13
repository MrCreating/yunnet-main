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
		$this->currentCacheServer->addServer('127.0.0.1', 11211);
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

	public function putItem ($name, $value): Cache
	{
		$this->currentCacheServer->set($this->currentCacheName . '/' . $name, strval($value));

		return $this;
	}

	public function removeItem ($name): Cache
	{
		$this->currentCacheServer->delete($this->currentCacheName . '/' . $name);
		
		return $this;
	}
}

?>