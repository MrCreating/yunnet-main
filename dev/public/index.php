<?php
/**
 * Start point of dev platform!!!
*/

use unt\objects\Context;

require_once __DIR__ . '/../../bin/functions/dev_functions.php';

// variables
$is_logged = Context::get()->isLogged();
$is_mobile = Context::get()->isMobile();

$requested_page = explode('?', strtolower($_SERVER['REQUEST_URI']))[0];

if (strtoupper($_SERVER['REQUEST_METHOD']) === "POST")
{
	$requested_page = explode('/', $requested_page)[1];
	if ($requested_page === "")
		$requested_page = "main";

	require __DIR__ . '/' . $requested_page . '.php';

	die(json_encode(array('error' => 1)));
}

require __DIR__ . '/../../pages/page_templates.php';

die(default_page_template($is_mobile, Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));
