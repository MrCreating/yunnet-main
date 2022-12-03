<?php

/**
 * LongPolling getter.
 */

header('Access-Control-Allow-Origin: ' . unt\functions\get_page_origin());
header("Access-Control-Allow-Credentials: true");

if (!Context::get()->isLogged() || intval(Context::get()->getCurrentUser()->isBanned()))
    die(json_encode(array()));

$user_id = Context::get()->getCurrentUser()->getId();

$rand = rand(1, 9999999);
$done = openssl_encrypt(strval($user_id.'_'.strval(rand(1, 1000000000)).'_permissions'), 'AES-256-OFB', strval(rand(1, 10000000000)), 0, strval(rand(1, 1000000000)), $rand);

Cache::getCacheServer()->set($done, intval($user_id));
$result = array('url'=> Project::getDefaultDomain() . ':8080?mode=listen&state=polling&key='.urlencode($done), 'last_event_id' => 0, 'owner_id' => intval($user_id));

header("Content-Type: application/json");

die(json_encode($result));
?>