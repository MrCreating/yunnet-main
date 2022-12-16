<?php

namespace unt\objects;

use unt\platform\Cache;
use unt\platform\Data;

/**
 * Context class. Have a DataBase connection, current user account, e.t.c
*/

class Context extends BaseObject
{
	private ?Session $currentSession;

	public function __construct ()
	{
		parent::__construct();

		$user_id = intval($_SESSION['user_id']);
		$session = new Session(strval($_SESSION['session_id']));

		if ($session->valid() && $session->isLogged())
		{
			$this->currentSession = $session;

			if ($this->getCurrentSession()->isLogged())
			{
				\unt\functions\update_online_time($this->currentConnection, intval($this->getCurrentUser()->getOnline()->lastOnlineTime), $this->getCurrentUser()->getId());
			}
		} else {
            $this->currentSession = NULL;
        }
	}

	public function allowToUseUnt (): bool
	{
		if (!$this->isLogged()) return false;
		if ($this->getCurrentUser()->isBanned()) return false;

		return true;
	}

	public function getCurrentUser (): ?Entity
	{
		$session = $this->getCurrentSession();
		if ($session)
		{
			return $session->getCurrentUser();
		}

		return NULL;
	}

	public function isLogged (): bool
	{
		if (!$this->getCurrentSession())
			return false;

		return $this->getCurrentSession()->isLogged();
	}

	public function isMobile (): bool
	{
		return preg_match(
				"/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", 
				$_SERVER["HTTP_USER_AGENT"]
			) || substr($_SERVER['HTTP_HOST'], 0, 2) === 'm.';
	}

	public function getCurrentSession (): ?Session
	{
		return $this->currentSession;
	}

	public function getLanguage (): Data
	{
		$languageCodes = ["en", "ru"];

		$languageCode = "ru";

		$current_user = $this->getCurrentUser();

		if ($current_user)
		{
			$languageCode = $current_user->getSettings()->getSettingsGroup(Settings::ACCOUNT_GROUP)->getLanguageId();
		} else
		{
			$langCode = strtolower(substr(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2));
			if (in_array($langCode, $languageCodes))
			{
				$languageCode = $langCode;
			}
		}

		$cache = Cache::getCacheServer();
		$lang  = json_decode($cache->get('lang_' . $languageCode), true);

		if (!$lang)
		{
			$language_json = file_get_contents(PROJECT_ROOT . '/bin/languages/' . $languageCode);

			$cache->set('lang_' . $languageCode, $language_json);
			$lang = json_decode($language_json, true);
		}

		return new Data($lang);
	}

	public function Logout (): bool
	{
		session_start();
		$this->getCurrentSession()->end();

		$_SESSION = [];

		session_write_close();
		return true;
	}

	////////////////////////////////////////
	public static function get (): Context
	{
		static $lastContext;
        if (!isset($lastContext))
            $lastContext = new self();

        return $lastContext;
	}
}

?>