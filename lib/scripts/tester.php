<?php

require 'bin/base_functions.php';
$connection = DataBaseManager::getConnection();
DataBaseManager::getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

$i = 0;
while ($i < 1000000)
{
	DataBaseManager::getConnection()->prepare("INSERT INTO users.test (valud) VALUES (1);")->execute();
	$i++;

	echo "[ ".$i." ] OK!".PHP_EOL;
}
?>