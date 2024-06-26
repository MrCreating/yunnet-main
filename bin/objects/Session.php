<?php

namespace unt\objects;

use unt\platform\Cache;

/**
 * Session class. Valid for tokens and auth.
*/

class Session extends BaseObject
{
	private Entity $bound_user;

	private bool $isMobile;

	private string $sessionId;

	private bool $isValid;

	private bool $isLogged;

	private bool $isCurrent;

	private string $ip_address;

	private int $loginTime;

	private string $loginInfo;

	public function __construct (string $id)
	{
        parent::__construct();

		$this->isLogged  = false;
		$this->isCurrent = false;
		$this->isValid   = false;
		$this->isMobile  = false;

		$cache = Cache::getCacheServer();

		$sessions_list = unserialize($cache->get("sessions_" . $_SESSION['user_id']));
		if ($sessions_list)
		{
			$current_session_info = $sessions_list[$id];
			if ($current_session_info)
			{
				$this->isValid = true;
			}

			if ($current_session_info && !$current_session_info["is_closed"])
			{
				$this->bound_user = intval($_SESSION['user_id']) > 0 ? new User(intval($_SESSION['user_id'])) : new Bot(intval($_SESSION['user_id']));
				if ($this->bound_user->valid())
				{
					$this->ip_address = $current_session_info['ip'];
					$this->isMobile  = $current_session_info['data']['mobile'];
					$this->loginTime = $current_session_info['data']['time'];
					$this->sessionId = $current_session_info['id'];
					$this->loginInfo = $current_session_info['login_data'];
					$this->isLogged  = ($this->valid() && $this->bound_user->getId() === intval($_SESSION['user_id']) && $this->getId() === $_SESSION['session_id']);
				}
			}
		}
	}

	public function getId (): string
	{
		return $this->sessionId;
	}

	public function getCurrentUser (): Entity
	{
		return $this->bound_user;
	}

	public function isMobile (): bool
	{
		return $this->isMobile;
	}

	public function isLogged (): bool
	{
		return $this->isLogged;
	}

	public function end (): bool
	{
		if (!$this->valid())
			return false;

		$cache = Cache::getCacheServer();

		$sessions_list = unserialize($cache->get("sessions_" . $this->getCurrentUser()->getId()));
		if (!$sessions_list)
			return false;

		if ($sessions_list[$this->getId()])
		{
			if ($this->isCloseable() || intval($_SESSION['restore_stage']) === 3)
			{
				unset($sessions_list[$this->getId()]);
				$cache->set("sessions_" . $this->getCurrentUser()->getId(), serialize($sessions_list));

				if ($this->getId() === $_SESSION['session_id'])
					$_SESSION = [];

				return true;
			}
		}

		return false;
	}

	public function setAsCurrent (): Session
	{
		session_start();

		if (isset($_SESSION['session_id']))
		{
			$old_session = new Session($_SESSION['session_id']);
			if ($old_session->valid() && $old_session->isLogged())
				$old_session->end();
		}

		$_SESSION = [];

		$_SESSION['session_id'] = $this->getId();
		$_SESSION['user_id'] = $this->getCurrentUser()->getId();
		session_write_close();

		$_SERVER['context'] = new Context();

		return $this;
	}

	public function isCurrent (): bool
	{
		return $this->isCurrent;
	}

	public function getIP (): string
	{
		return $this->ip_address;
	}

	public function getLoginTime (): int
	{
		return $this->loginTime;
	}

	public function getLoginInfo (): string
	{
		return $this->loginInfo;
	}

	public function toArray (): array
	{
		return [
			'ip'           => $this->getIP(),
			'login_data'   => $this->getLoginInfo(),
			'is_closeable' => $this->isCloseable(),
			'id'           => $this->getId(),
			'data'         => [
				'mobile'  => $this->isMobile(),
				'user_id' => $this->getCurrentUser()->getId(),
				'time'    => $this->getLoginTime()
			]
		];
	}

	public function isCloseable (): bool
	{
		return (((time() - $this->getLoginTime()) > 86400) || ($this->getId() === $_SESSION['session_id']));
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	// static actions
	public static function start (int $user_id): ?Session
	{
		session_start();

		$_SESSION['user_id'] = $user_id;

		$cache = Cache::getCacheServer();

		$sessions_list = unserialize($cache->get("sessions_" . $user_id));
		if (!$sessions_list)
			$sessions_list = [];

		$session_id = str_shuffle('asfjshdl129038102947afima');
		$session_array = array (
			'ip'         => $_SERVER['REMOTE_ADDR'],
			'login_data' => $_SERVER['HTTP_USER_AGENT'],
			'is_closed'  => false,
			'id'         => $session_id,
			'data'       => array (
				'mobile'  => Context::get()->isMobile(),
				'user_id' => $user_id,
				'time'    => time()
			)
		);

		$sessions_list[$session_id] = $session_array;
		$cache->set("sessions_" . $user_id, serialize($sessions_list));

		$session = new Session($session_id);

		session_write_close();
		if ($session->valid())
		{
			return $session;
		}

		return NULL;
	}

	public static function getList (): array
	{
		$cache  = Cache::getCacheServer();
		$result = [];

		$sessions_list = unserialize($cache->get("sessions_" . $_SESSION['user_id']));

		foreach ($sessions_list as $id => $session_array) {
			$session = new Session($id);
			if ($session->valid())
			{
				$result[] = $session;
			}
		}

		return $result;
	}
}

?>