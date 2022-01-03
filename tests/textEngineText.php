<?php

require_once __DIR__ . '/../bin/objects/project.php';
require_once __DIR__ . '/../bin/base_functions.php';
require_once __DIR__ . '/../bin/platform-tools/emitters.php';
require_once __DIR__ . '/../bin/functions/messages.php';

$a = get_local_chat_id(-2);
$b = get_last_uid();

die(var_dump($a, $b));
?>