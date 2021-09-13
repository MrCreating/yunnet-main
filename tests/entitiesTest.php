<?php

require __DIR__ . '/../bin/objects/entities.php';

$user = new User(2);

die(var_dump($user->getCurrentPhoto()));
?>