<?php

require_once __DIR__ . '/../../../bin/objects/chat.php';
require_once __DIR__ . '/../../../bin/objects/dialog.php';
require_once __DIR__ . '/../../../bin/objects/conversation.php';

$result = Entity::runAs(69, function (Context $context) {
	$chat = Chat::findById("1");

	$event_type = strtolower($headers['X-GitHub-Event']);

	die(var_dump($event_type));
	if ($event_type === 'push')
	{
		$event = json_decode(file_get_contents('php://input'), true);

		$messageText = '
***[GitHub]***

========== INFO ===========
Commit uploaded by: **' . $event['sender']['login'] . '**
Commit uploaded at: **' . $event['head_commit']['timestamp'] . '**
';

		if ($event['ref'] !== "refs/heads/master")
			$messageText .= '
***NOT IN THE MASTER***
';
	
		$files_list = array_merge($event['head_commit']['modified'], $event['head_commit']['added']);
		if (count($files_list) > 0)
		{
			$messageText .= '
**Changed files list:**';

			foreach ($files_list as $index => $filename) 
			{
				$index += 1;

				$messageText .= "
*{$index}*. {$filename}";
			}
		}

		$removed_files_list = $event['head_commit']['removed'];
		if (count($removed_files_list) > 0)
		{
			$messageText .= '
**Removed files list:**';

			foreach ($removed_files_list as $index => $filename) 
			{
				$index += 1;

				$messageText .= "
*{$index}*. {$filename}";
			}
		}

		$chat->sendMessage($messageText);
	}
});

die(json_encode(array('response' => intval($result))));
?>