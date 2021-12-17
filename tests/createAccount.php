<?php

require_once __DIR__ . '/../bin/objects/project.php';
require_once __DIR__ . '/../bin/base_functions.php';

$user = User::findById(69);

if (!$user)
{
	echo '[!] User not found. Creating...' . PHP_EOL;
	$user = User::create('Администрация', '', '', password_hash('', PASSWORD_DEFAULT), 1);

	if ($user)
	{
		echo '[OK] New user id is ' . $user->getId() . PHP_EOL;
		echo '[OK] User created. Success.' . PHP_EOL;
	}
} else {
	echo '[OK] User found. Success.' . PHP_EOL;
}

?>