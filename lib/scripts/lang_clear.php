<?php

/**
 * Language clear script.
*/
error_reporting(0);

echo '[+] Connecting to cache...' . PHP_EOL;
$start_time = microtime();

$mem = new Memcached();
$mem->addServer('127.0.0.1', 11211);

echo '[+] Clearing cache...' . PHP_EOL;
$mem->delete('lang_en');
$mem->delete('lang_ru');

$mem->delete('lang_dev_ru');
$mem->delete('lang_dev_en');

echo '[+] All done with ' . (microtime() - $start_time) . ' sec.' . PHP_EOL;
?>