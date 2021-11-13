<?php

require_once __DIR__ . '/../../../bin/objects/chat.php';

$session = Session::start(69);

$chat = Chat::findById("1");

$chat->sendMessage(1, 'GitHub Event Received!');
$session->end();

die(json_encode(array('response' => 1)));
?>