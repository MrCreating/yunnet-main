<?php

/**
 * THIS IS A CONFIG CLASS
 * IT CONTROLS ALL PROJECT INFO
 * DO NOT CONSTRUCT IT WITHOUT NEEDED
*/

error_reporting(0);

class Project
{
	// URLS
	public const CONNECTION_HEAD = "https://";
	public const PROJECT_URL     = "yunnet.ru";

	public const DEFAULT_URL     = self::CONNECTION_HEAD .             self::PROJECT_URL;
	public const MOBILE_URL      = self::CONNECTION_HEAD . "m."      . self::PROJECT_URL;
	public const ATTACHMENTS_URL = self::CONNECTION_HEAD . "d-1."    . self::PROJECT_URL;
	public const DEVELOPERS_URL  = self::CONNECTION_HEAD . "dev."    . self::PROJECT_URL;
	public const THEMES_URL      = self::CONNECTION_HEAD . "themes." . self::PROJECT_URL;
	///////

	// current yunNet. version
	public const VERSION = 6;

	// memcached IP
	public const CACHE_IP = "memcached";

	// memcached PORT
	public const CACHE_PORT = 11211;

	// default DB username
	public const DB_USERNAME = "root";

	// default DB password
	public const DB_PASSWORD = "default-prod-unt-user-iA22021981_";

	// get default host
	public static function getProjectUrl ()
	{
		return ($h = gethostname()) ? $h : "212.109.219.153"
	}

	public static function isClosed (): bool
	{
		return false;
	}

	public static function isRegisterClosed (): bool
	{
		return false;
	}

	public static function getRegisteredUsersCount (): int
	{
		return 0;
	}

	public static function getRegisteredBotsCount (): int
	{
		return 0;
	}

	public static function getSentMessagesCount (): int
	{
		return 0;
	}

	public static function isDefaultLink ($url): bool
	{
		return in_array($url, [
			'/',         '/notifications', '/friends',
			'/messages', '/settings',      '/audios',
			'/edit',     '/login',          '/flex',
			'/groups',   '/chats',         '/restore',
			'/walls',    '/themer',        '/themes',
			'/upload',   '/documents',     '/bots',
			'/sessions', '/dev',           '/cookies',
			'/about',    '/register',      '/terms',
			'/rules',    '/groups',        '/archive'
		]);
	}

	public static function isLinkUsed (string $link): bool
	{
		if (self::isDefaultLink($link)) return true;

		$link = substr($link, 0, 1) == '/' ? substr($link, 1, strlen($link)) : $link;

		return Entity::findByScreenName($link) != NULL;
	}

	/////////////////////////////////////////////////////////
	public function __construct ()
	{
		throw new Exception('Unable to create STATIC class');
	}
}

?>