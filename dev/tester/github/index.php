<?php

require_once __DIR__ . '/../../../bin/objects/chat.php';
require_once __DIR__ . '/../../../bin/objects/dialog.php';
require_once __DIR__ . '/../../../bin/objects/conversation.php';

ini_set('display_errors', 1);

$result = Entity::runAs(69, function (Context $context) {
	$chat = Chat::findById("1");

	$event = json_decode(file_get_contents('php://input'), true);

	$messageText = '***[GITHUB EVENT RECEIVED]***\n';

	$messageText .= '\n=======================\n';
	$messageText .= '**Changed files list:**';

	$chat->sendMessage($messageText);
});

die(json_encode(array('response' => intval($result))));
?>