<?php

require_once __DIR__ . '/../../../bin/objects/chat.php';
require_once __DIR__ . '/../../../bin/objects/dialog.php';
require_once __DIR__ . '/../../../bin/objects/conversation.php';

$result = Entity::runAs(69, function (Context $context) {
	$chat = Chat::findById("1");

	$chat->sendMessage('GitHub Event Received!');
});

die(json_encode(array('response' => intval($result))));
?>