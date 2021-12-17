<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$m = new Memcached();

$m->addServer("localhost", 11211);

die(var_dump($m->get("lang_ru")));
?>