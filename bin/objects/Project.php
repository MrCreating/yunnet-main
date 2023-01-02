<?php

namespace unt\objects;

use Exception;
use Memcached;
use unt\platform\Cache;

/**
 * THIS IS A CONFIG CLASS
 * IT CONTROLS ALL PROJECT INFO
 * DO NOT CONSTRUCT IT WITHOUT NEEDED
*/

class Project extends BaseObject
{
    // current yunNet. version
    public const VERSION = 6;

    // memcached IP
    public const CACHE_IP = "memcached";

    // memcached PORT
    public const CACHE_PORT = 11211;

    public static function getConnectionHead(): string
    {
        return (self::isProduction() ? 'https://' : 'http://');
    }

    public static function getOrigin (): string
    {
        $link_without_params = (self::getConnectionHead()) . explode('/', explode('?', $_SERVER['HTTP_REFERER'])[0])[2];

        return substr($link_without_params, 0, strlen($link_without_params));
    }

    public static function getProjectDomain(): string
    {
        if (getenv("UNT_PRODUCTION") === '1') return 'yunnet.ru';

        return 'localhost';
    }

    public static function getRulesText (): string
    {
        $lang = Context::get()->getLanguage()->id;

        return file_get_contents(__DIR__ . '/../languages/policy/' . $lang . '/rules');
    }

    public static function getTermsText (): string
    {
        $lang = Context::get()->getLanguage()->id;

        return file_get_contents(__DIR__ . '/../languages/policy/' . $lang . '/terms');
    }

    public static function getDefaultDomain(): string
    {
        return self::getConnectionHead() . self::getProjectDomain();
    }

    public static function getMobileDomain(): string
    {
        return self::getConnectionHead() . "m." . self::getProjectDomain();
    }

    public static function getAttachmentsDomain(): string
    {
        return "https://d-1.yunnet.ru";
    }

    public static function getDevDomain(): string
    {
        return self::getConnectionHead() . "dev." . self::getProjectDomain();
    }

    public static function getThemesDomain(): string
    {
        return self::getConnectionHead() . "themes." . self::getProjectDomain();
    }

    public static function toggleClose(): bool
    {
        $result = self::isClosed();

        Cache::getCacheServer()->set('closed_project', strval(intval(!$result)));

        return !$result;
    }

    public static function toggleRegistrationClose(): bool
    {
        $result = Project::isRegisterClosed();

        Cache::getCacheServer()->set('closed_register', strval(intval(!$result)));

        return !$result;
    }

	public static function isClosed (): bool
	{
        return intval(Cache::getCacheServer()->get('closed_project'));
	}

	public static function isRegisterClosed (): bool
	{
        return intval(Cache::getCacheServer()->get('closed_register'));
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

	public static function isDefaultLink (string $url): bool
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

    public static function isProduction (): bool
    {
        return getenv('UNT_PRODUCTION') == '1';
    }

    public static function getFiles (string $filePath = PROJECT_ROOT): array
    {
        $directory = opendir($filePath);
        $result = [];

        $i = 0;
        while (false !== ($file = readdir($directory)))
        {
            if ($file === '.' || $file === '..') continue;

            $i++;

            $result[] = [
                'name' => $file,
                'type' => is_dir($filePath . $file) ? "directory" : "file",
                'id'   => $i
            ];
        }

        closedir($directory);

        return $result;
    }

	/////////////////////////////////////////////////////////

    /**
     * @throws Exception
     */
    public function __construct ()
	{
        parent::__construct();

		throw new Exception('Unable to create STATIC class');
	}
}

?>