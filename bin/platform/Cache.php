<?php

namespace unt\platform;

use Memcached;
use unt\objects\BaseObject;
use unt\objects\Project;

/**
 * Main cache class
 * May contain users, and other.
*/

class Cache extends BaseObject
{
	private ?Memcached $currentCacheServer;
	private string $currentCacheName;

	// load cache server. May be different.
	function __construct (string $cacheName = "default")
	{
        parent::__construct();

		$this->currentCacheServer = new Memcached();
		$this->currentCacheServer->addServer(Project::CACHE_IP, Project::CACHE_PORT);
		$this->currentCacheName = $cacheName;
	}

	public function getCacheName (): string
	{
		return $this->currentCacheName;
	}

	public function getItem (string $name): string
	{
        return $this->currentCacheServer->get($this->currentCacheName . '/' . $name);
	}

	public function putItem (string $name, string $value, int $aliveTime = 86400): Cache
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

	public function removeItem (string $name): Cache
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