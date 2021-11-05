<?php

require_once __DIR__ . '/../bin/objects/project.php';
require_once __DIR__ . '/../bin/base_functions.php';
require_once __DIR__ . '/../bin/objects/message.php';
require_once __DIR__ . '/../bin/objects/conversation.php';
require_once __DIR__ . '/../bin/objects/dialog.php';

$_SESSION = [];
$_SESSION['user_id'] = 1;

die(var_dump(Chat::getList(1)[0]->getLastMessage()->getAttachments()[0]->like()));
?>