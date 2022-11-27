<?php

require_once __DIR__ . '/../bin/objects/Project.php';
require_once __DIR__ . '/../bin/base_functions.php';

$entity = User::findByEMAIL('mrcreating2002@gmail.com');

die(var_dump($entity));

?>