<?php

require_once __DIR__ . '/../../../bin/objects/chat.php';
require_once __DIR__ . '/../../../bin/objects/dialog.php';
require_once __DIR__ . '/../../../bin/objects/conversation.php';

$result = Entity::runAs(69, function (Context $context) {
	$chat = Chat::findById("1");

	$event = json_decode(file_get_contents('php://input'), true);

$messageText = '
***[GitHub]***

=======================
**Changed files list:**
';
	
	$files_list = array_merge($event['head_commit']['modified'], $event['head_commit']['added']);
	foreach ($files_list as $index => $filename) {
		$messageText .= "
*{$i}*. {$filename}
";
	}

	$chat->sendMessage($messageText);
});

die(json_encode(array('response' => intval($result))));
?>