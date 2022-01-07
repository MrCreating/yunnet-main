<?php

require_once __DIR__ . '/objects/session.php';

/**
 * Context class. Have a DataBase connection, current user account, e.t.c
*/

class Context 
{
	private $current_session    = NULL;
	private $current_connection = NULL;

	public function __construct ()
	{
		$this->current_connection = DataBaseManager::getConnection();

		$user_id = intval($_SESSION['user_id']);
		$session = new Session(strval($_SESSION['session_id']));

		parse_str(explode("?", $_SERVER["REQUEST_URI"])[1], Request::get()->data);

		if ($session->valid() && $session->isLogged())
		{
			$this->current_session = $session;

			if ($this->getCurrentSession()->isLogged())
			{
				update_online_time($this->getConnection(), intval($this->getCurrentUser()->getOnline()->lastOnlineTime), $this->getCurrentUser()->getId());
			}
		}
	}

	public function allowToUseUnt (): bool
	{
		if (!$this->isLogged()) return false;
		if ($this->getCurrentUser()->isBanned() && $this->getCurrentUser()->getAccessLevel() < 3) return false;

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
			);
	}

	public function getSessions (): array
	{
		return Session::getList();
	}

	public function getCurrentSession (): ?Session
	{
		return $this->current_session;
	}

	public function getLanguage (): Data
	{
		$languageCodes = ["en", "ru"];

		$languageCode = "ru";

		$current_user = $this->getCurrentUser();

		if ($current_user)
		{
			$languageCode = $current_user->getSettings()->getSettingsGroup('account')->getLanguageId();
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
			$language_json = file_get_contents(__DIR__ . '/languages/' . $languageCode);

			$cache->set('lang_' . $languageCode, $language_json);
			$lang = json_decode($language_json, true);
		}

		return new Data($lang);
	}

	public function getConnection ()
	{
		return $this->current_connection;
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
		return isset($_SERVER['context']) ? $_SERVER['context'] : (function () {
			$_SERVER['context'] = new Context();

			return $_SERVER['context'];
		})();
	}
}

?>