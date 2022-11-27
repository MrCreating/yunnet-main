<?php

require_once __DIR__ . '/../bin/objects/Project.php';
require_once __DIR__ . '/../bin/base_functions.php';

$evt = new EventEmitter();

$_SESSION['user_id'] = 1;

$res = $evt->event(['event' => 'test'], [1, -2, -3, 1], [2, 3, 3, 5]);

die(var_dump($res));
?>