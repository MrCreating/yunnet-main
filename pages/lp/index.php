<?php

/**
 * LongPolling getter.
*/

if (!class_exists('Context'))
	require_once __DIR__ . '/../../bin/context.php';

header('Access-Control-Allow-Origin: '.get_page_origin());
header("Access-Control-Allow-Credentials: true");

$context = new Context();

if (!$context->isLogged() || intval($context->getCurrentUser()->isBanned()))
	die(json_encode(array()));

$lp_data = get_polling_data(Cache::getCacheServer(), $context->getCurrentUser()->getId(), "polling");

header("Content-Type: application/json");
die(json_encode($lp_data));
?>