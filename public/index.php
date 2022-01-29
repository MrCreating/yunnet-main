<?php

/**
 * 
 * yunnet frontend code.
 * This file get into internal api
 * and loads current user object
 *
 */

// if ($_SERVER['REMOTE_ADDR'] != "31.40.55.185")
//	die('С 26 июля 2020 года 12:00 по МСК yunnet 4 закрыт. Ждите выхода yunnet 5. (Примерно 15-21 августа 2020 года)');

// getting the subdomain to do actions.
$to             = explode('.', strtolower($_SERVER['HTTP_HOST']))[0];
$requested_page = explode('?', strtolower($_SERVER['REQUEST_URI']))[0];

$subdomains = [
	'm', 'api', 'dev', 'd-1', 'yunnet', 'lp', 'themes', 'auth', 'test'
];

if (!in_array($to, $subdomains))
{
	//die(header("Location: " . getenv('UNT_PRODUCTION' === '1' ? 'https://yunnet.ru/' : 'http://localhost')));
}

// defines a page and loads the core context
define('REQUESTED_PAGE', $requested_page);

require_once __DIR__ . '/../bin/objects/project.php';
require_once __DIR__ . '/../bin/base_functions.php';

if (getenv('UNT_PRODUCTION') !== '1')
{
	session_write_close();
	ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']);
	session_start();
}

// checking domains.
switch ($to)
{
	case "api":
		die(require_once __DIR__ . '/../api/index.php');
	case "dev":
		die(require_once __DIR__ . '/../pages/dev/index.php');
	case "d-1":
		die(require_once __DIR__ . '/../attachments/index.php');
	case "lp":
		die(require_once __DIR__ . '/../pages/lp/index.php');
	case "themes":
		die(require_once __DIR__ . '/../attachments/themes.php');
	case "auth":
		die(require_once __DIR__ . '/../pages/widgets/auth/index.php');
	case "test":
		die(require_once __DIR__ . '/../dev/tester/index.php');
}

die(require_once __DIR__ . '/../pages/init.php');
?>