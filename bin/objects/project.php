<?php

/**
 * THIS IS A CONFIG CLASS
 * IT CONTROLS ALL PROJECT INFO
 * DO NOT CONSTRUCT IT WITHOUT NEEDED
*/

class Project
{
	// current yunNet. version
	public const VERSION = 6;

	// memcached IP
	public const CACHE_IP = "127.0.0.1";

	// memcached PORT
	public const CACHE_PORT = 11211;

	// default DB username
	public const DB_USERNAME = "";

	// default DB password
	public const DB_PASSWORD = "";

	// project domain - can be changed
	public const DEFAULT_DOMAIN = "yunnet.ru";

	// mobile url
	public const DEFAULT_MOBILE_URL = ("m." . Project::DEFAULT_DOMAIN);

	// attachments url
	public const DEFAULT_ATTACHMENTS_URL = ("d-1." . Project::DEFAULT_DOMAIN);

	// scripts url
	public const DEFAULT_SCRIPTS_URL = ("dev." . Project::DEFAULT_DOMAIN);

	// themes url
	public const DEFAULT_THEMES_URL = ("themes" . Project::DEFAULT_DOMAIN);

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

	public static function getContext (): Context
	{
		return isset($_SERVER['context']) ? $_SERVER['context'] : (function () {
			$_SERVER['context'] = new Context();
		})();
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