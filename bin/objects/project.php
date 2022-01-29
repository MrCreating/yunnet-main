<?php

/**
 * THIS IS A CONFIG CLASS
 * IT CONTROLS ALL PROJECT INFO
 * DO NOT CONSTRUCT IT WITHOUT NEEDED
*/

error_reporting(0);
//ini_set('display_errors', 1);

class Project
{
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

	// test DB password
	public const DB_TEST_PASSWORD = "unt-user-test-pc2021_die";

	public static function getConnectionHead ()
	{
		return (getenv('UNT_PRODUCTION') === '1' ? 'https://' : 'http://');
	}

	public static function getProjectDomain () 
	{
		if (getenv("UNT_PRODUCTION") === '1') return 'yunnet.ru';

		return 'localhost';
	}

	public static function getDefaultDomain ()
	{
		return self::getConnectionHead() . self::getProjectDomain();
	}

	public static function getMobileDomain ()
	{
		return self::getConnectionHead() . "m." . self::getProjectDomain();
	}

	public static function getAttachmentsDomain ()
	{
		return "https://d-1.yunnet.ru";
	}

	public static function getDevDomain ()
	{
		return self::getConnectionHead() . "dev." . self::getProjectDomain();
	}

	public static function getThemesDomain ()
	{
		return self::getConnectionHead() . "themes." . self::getProjectDomain();
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
		if (self::isDefaultLink('/' . $link) || self::isDefaultLink($link)) return true;

		$link = substr($link, 0, 1) == '/' ? substr($link, 1, strlen($link)) : $link;

		if (substr($link, 0, 5) === 'photo') return true;
		if (substr($link, 0, 4) === 'wall') return true;
		if (substr($link, 0, 4) === 'poll') return true;

		return Entity::findByScreenName($link) != NULL;
	}

	/////////////////////////////////////////////////////////
	public function __construct ()
	{
		throw new Exception('Unable to create STATIC class');
	}
}

?>