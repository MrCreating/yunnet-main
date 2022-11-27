<?php

require_once __DIR__ . '/../bin/objects/Project.php';
require_once __DIR__ . '/../bin/base_functions.php';
require_once __DIR__ . '/../bin/objects/Message.php';
require_once __DIR__ . '/../bin/objects/Conversation.php';
require_once __DIR__ . '/../bin/objects/Dialog.php';

$_SESSION = [];
$_SESSION['user_id'] = 1;

die(var_dump(Chat::getList(1)[0]->getLastMessage()->getAttachments()[0]->like()));
?>