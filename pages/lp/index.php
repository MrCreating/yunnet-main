<?php

/**
 * LongPolling getter.
*/

header('Access-Control-Allow-Origin: ' . get_page_origin());
header("Access-Control-Allow-Credentials: true");

if (!Context::get()->isLogged() || intval(Context::get()->getCurrentUser()->isBanned()))
	die(json_encode(array()));

$lp_data = get_polling_data(Cache::getCacheServer(), Context::get()->getCurrentUser()->getId(), "polling");

header("Content-Type: application/json");

die(json_encode($lp_data));
?>