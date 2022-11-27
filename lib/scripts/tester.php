<?php

require 'bin/base_functions.php';
$connection = DataBaseManager::getConnection();
$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

$i = 0;
while ($i < 1000000)
{
	$connection->prepare("INSERT INTO users.test (valud) VALUES (1);")->execute();
	$i++;

	echo "[ ".$i." ] OK!".PHP_EOL;
}
?>