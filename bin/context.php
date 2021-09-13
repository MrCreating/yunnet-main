<?php

/**
 * Context class. Have a DataBase connection, current user account, e.t.c
*/
if (!class_exists('Session'))
	require __DIR__ . '/objects/session.php';
if (!class_exists('DataBaseConnection'))
	require __DIR__ . '/database.php';
if (!function_exists('check_mobile'))
	require __DIR__ . '/base_functions.php';

/*if (!function_exists('get_page_origin'))
	require __DIR__ . '/base_functions.php';

// time constants
define('ONE_DAY', 86400);
define('TWO_DAYS', ONE_DAY*2);

class Context
{
	public $user_id   = 0;
	public $isLogged  = false;
	public $isMobile  = false;

	public $lang      = [];
	public $pages     = [];
	public $request   = [];
	public $profile   = [];

	private $utils     = [
		"connection" => false,
		"cache"      => false
	];

	function __construct() 
	{
		$this->utils["connection"] = self::getConnection();

		$this->user_id          = intval($_SESSION['user_id']);

		if (!class_exists('User'))
			require __DIR__ . '/objects/entities.php';
		
		$this->profile["user"] = new User($this->user_id);
		if (intval($this->profile["user"]->profile['is_banned']))
		{
			$this->getCache()->set('banned_'.$this->user_id, '1');
		} else {
			$this->getCache()->set('banned_'.$this->user_id, '0');
		}

		$this->isLogged         = self::checkLoginState();
		$this->isMobile         = check_mobile();
		$this->lang             = self::getLanguage($this->utils[0]);
		$this->pages            = get_default_pages();

		// build request array
		parse_str(explode("?", $_SERVER["REQUEST_URI"])[1], $this->request);
	}

	// gets a current language
	function getLanguage ()
	{
		return get_language($this->utils["connection"]);
	}

	// changes the user id
	function changeUserId ($user_id)
	{
		return $this->user_id = $user_id;
	}

	// checks login state
	function checkLoginState ()
	{
		// checks sessions list
		$sessions = self::getSessions();
		if ($sessions[$_SESSION['session_id']]) 
		{
			if (!$this->profile["user"]->valid()) return false;

			if ($sessions[$_SESSION['session_id']]["is_closed"])
				return false;

			return true;
		}

		return false;
	}

	// returns database connection object
	function getConnection () 
	{
		if ($this->utils["connection"])
			return $this->utils["connection"];

		$connection = get_database_connection();

		$this->utils["connection"] = $connection;
		return $connection;
	}

	// returns cache connection object
	function getCache () 
	{
		if ($this->utils["cache"])
			return $this->utils["cache"];

		$cache = get_cache();

		$this->utils["cache"] = $cache;
		return $cache;
	}

	// returns sessions array
	function getSessions () 
	{
		$cache    = self::getCache();
		$sessions = unserialize($cache->get('sessions_'.$this->user_id));

		if (!$sessions) 
		{
			return [];
		}
		
		return $sessions;
	}

	// creates an auth session
	function AuthSession ($user_id = 0)
	{
		session_start();

		if ($user_id === 0)
			$user_id = $this->user_id;

		$connection = $this->utils["connection"];

		self::changeUserId($user_id);
		$sessions = self::getSessions();

		// creating a new session.
		$session_id = str_shuffle('asfjshdl129038102947afima');
		$session = array(
			'ip'         => $_SERVER['REMOTE_ADDR'],
			'login_data' => $_SERVER['HTTP_USER_AGENT'],
			'is_closed'  => false,
			'id'         => $session_id,
			'data'       => array (
				'mobile'  => $this->isMobile,
				'user_id' => $user_id,
				'time'    => time()
			)
		);
		$sessions[$session_id] = $session;

		// saving session list.
		self::writeSessions($sessions);

		// Now sending notification and redirect header.
		$_SESSION['session_id'] = $session_id;
		$_SESSION['user_id'] = $user_id;

		session_write_close();

		return true;
	}

	// ends session by id.
	function endSession ($id)
	{
		$sessions = self::getSessions();
		if (!$sessions[$id] || $sessions[$id]["is_closed"])
		{
			return false;
		}

		if (!((time() - $sessions[$_SESSION["session_id"]]["data"]["time"]) > TWO_DAYS))
		{
			return false;
		}

		unset($sessions[$id]);
		self::writeSessions($sessions);

		return true;
	}

	// logout from the account
	function Logout ()
	{
		session_start();

		if (intval($_SESSION["restore_stage"]) > 0)
		{
			$_SESSION = [];

			session_destroy();
		}

		if (intval($_SESSION["stage"]) > 2)
			return false;

		$sessions = self::getSessions();
		if ( isset($_SESSION['session_id']) )
		{
			unset($sessions[intval($_SESSION['session_id'])]);
		}

		session_destroy();
		self::writeSessions($sessions);

		return true;
	}

	// sessions writer
	function writeSessions ($sessions)
	{
		$cache = self::getCache();

		return $cache->set('sessions_'.$this->user_id, serialize($sessions));
	}

	// change the language
	function changeLanguage ($current_user, $lang_id)
	{
		session_start();

		$connection = $this->getConnection();

		$lang = 'en';
		$langs = [
			'ru', 'en'
		];

		if (in_array($lang_id, $langs))
			$lang = $lang_id;

		$_SESSION['lang'] = $lang;
		if ($this->isLogged) 
		{
			$settings = $this->profile['user']->getSettings()->getValues();
			$settings->lang = $lang;

			$connection->prepare("UPDATE users.info SET settings = ? WHERE id = ? LIMIT 1;")->execute([
				json_encode($settings),
				strval($this->user_id)
			]);
		}

		return true;
	}
}*/

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

	public function getCurrentUser ()
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
		return get_language($this->getConnection());
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