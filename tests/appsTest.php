<?php

require __DIR__ . '/../bin/base_functions.php';
require __DIR__ . '/../bin/objects/app.php';

$app = new App(11);

die(var_dump($app->setTitle('Android')->apply()));

?>