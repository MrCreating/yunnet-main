<?php

require __DIR__ . '/../bin/platform-tools/Cache.php';

$cache = new Cache("test");

$cache->putItem("test", 1);

$result = $cache->getItem("test");

echo $result . PHP_EOL;

?>