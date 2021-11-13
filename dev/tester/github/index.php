<?php

ini_set('display_errors', 1);

require_once __DIR__ . '/../../../bin/objects/chat.php';
require_once __DIR__ . '/../../../bin/objects/dialog.php';
require_once __DIR__ . '/../../../bin/objects/conversation.php';

$session = Session::start(69);
$session->setAsCurrent();

$chat    = Chat::findById("1");

$chat->sendMessage(1, 'GitHub Event Received!');

$session->end();

die(json_encode(array('response' => 1)));
?>