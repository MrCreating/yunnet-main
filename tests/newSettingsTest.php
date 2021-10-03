<?php

require __DIR__ . '/../bin/base_functions.php';

$user = new Bot(2);

die(var_dump($user->getSettings()));

?>