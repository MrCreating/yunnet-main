<?php
/**
 * 
 * yunnet frontend code.
 * This file get into internal api
 * and loads current user object
 *
 */

//ini_set("display_errors", 1);

// if ($_SERVER['REMOTE_ADDR'] != "31.40.55.185")
//	die('С 26 июля 2020 года 12:00 по МСК yunnet 4 закрыт. Ждите выхода yunnet 5. (Примерно 15-21 августа 2020 года)');

// getting the subdomain to do actions.
$to             = explode('.', strtolower($_SERVER['HTTP_HOST']))[0];
$requested_page = explode('?', strtolower($_SERVER['REQUEST_URI']))[0];

$subdomains = [
	'm', 'api', 'dev', 'd-1', 'yunnet', 'lp', 'themes', 'auth'
];

if (!in_array($to, $subdomains))
{
	die(header("Location: https://yunnet.ru"));
}

// defines a page and loads the core context
define('REQUESTED_PAGE', $requested_page);

require_once __DIR__ . '/../bin/base_functions.php';

// checking domains.
switch ($to)
{
	case $subdomains[1]:
		die(require __DIR__ . '/../api/index.php');
	case $subdomains[2]:
		die(require __DIR__ . '/../pages/dev/index.php');
	case $subdomains[3]:
		die(require __DIR__ . '/../attachments/index.php');
	case $subdomains[5]:
		die(require __DIR__ . '/../pages/lp/index.php');
	case $subdomains[6]:
		die(require __DIR__ . '/../attachments/themes.php');
	case $subdomains[7]:
		die(require __DIR__ . '/../pages/widgets/auth/index.php');
}

die(require_once __DIR__ . '/../pages/init.php');
?>