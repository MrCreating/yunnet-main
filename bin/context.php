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
		$user_id = intval($_SESSION['user_id']);
		$session = new Session(strval($_SESSION['session_id']));
	
		$this->current_connection = (new DataBaseConnection())->getPDOObject();

		$_SERVER['context'] = $this;
		$_SERVER['dbConnection'] = $this->getConnection();

		parse_str(explode("?", $_SERVER["REQUEST_URI"])[1], $_REQUEST);

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
		return check_mobile();
	}

	public function getSessions (): array
	{
		return Session::getList();
	}

	public function getCurrentSession (): ?Session
	{
		return $this->current_session;
	}

	public function getLanguage ()
	{
		return get_language($this->getConnection(), $this->getCurrentUser());
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
}

?>