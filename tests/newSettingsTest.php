<?php

require __DIR__ . '/../bin/base_functions.php';

$user = new User(1);

die(var_dump($user->getSettings()));

?>