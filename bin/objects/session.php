<?php

require_once __DIR__ . '/user.php';
require_once __DIR__ . '/bot.php';

/**
 * Session class. Valid for tokens and auth.
*/

class Session
{
	private $bound_user = NULL;

	private $is_mobile  = NULL;
	private $session_id = NULL;
	private $is_valid   = NULL;
	private $is_logged  = NULL;
	private $is_current = NULL;
	private $ip_address = NULL;
	private $login_time = NULL;
	private $login_info = NULL;

	public function __construct (string $id)
	{
		$this->is_logged  = false;
		$this->is_current = false;
		$this->is_valid   = false;
		$this->is_mobile  = check_mobile();		

		$cache = get_cache();

		$sessions_list = unserialize($cache->get("sessions_" . $_SESSION['user_id']));
		if ($sessions_list)
		{
			$current_session_info = $sessions_list[$id];
			if ($current_session_info)
			{
				$this->is_valid = true;
			}

			if ($current_session_info && !$current_session_info["is_closed"])
			{
				$this->bound_user = intval($_SESSION['user_id']) > 0 ? new User(intval($_SESSION['user_id'])) : new Bot(intval($_SESSION['user_id']));
				if ($this->bound_user->valid())
				{
					$this->ip_address = $current_session_info['ip'];
					$this->is_mobile  = $current_session_info['data']['mobile'];
					$this->login_time = $current_session_info['data']['time'];
					$this->session_id = $current_session_info['id'];
					$this->login_info = $current_session_info['login_data'];
					$this->is_logged  = ($this->valid() && $this->bound_user->getId() === intval($_SESSION['user_id']) && $this->getId() === $_SESSION['session_id']);
				}
			}
		}
	}

	public function getId (): string
	{
		return $this->session_id;
	}

	public function getCurrentUser (): Entity
	{
		return $this->bound_user;
	}

	public function isMobile (): bool
	{
		return $this->is_mobile;
	}

	public function isLogged (): bool
	{
		return $this->is_logged;
	}

	public function end (): bool
	{
		if (!$this->valid())
			return false;

		$cache = get_cache();

		$sessions_list = unserialize($cache->get("sessions_" . $this->getCurrentUser()->getId()));
		if (!$sessions_list)
			return false;

		if ($sessions_list[$this->getId()])
		{
			if ($this->isCloseable())
			{
				unset($sessions_list[$this->getId()]);
				$cache->set("sessions_" . $this->getCurrentUser()->getId(), serialize($sessions_list));

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

		return $this;
	}

	public function isCurrent (): bool
	{
		return $this->is_current;
	}

	public function getIP (): string
	{
		return $this->ip_address;
	}

	public function getLoginTime (): int
	{
		return $this->login_time;
	}

	public function getLoginInfo (): string
	{
		return $this->login_info;
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
		return $this->is_valid;
	}

	// static actions
	public static function start (int $user_id): ?Session
	{
		session_start();

		$_SESSION['user_id'] = $user_id;

		$cache = get_cache();

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
				'mobile'  => check_mobile(),
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
		$cache  = get_cache();
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