<?php
if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/../../bin/objects/Attachments.php';

$credentials = substr(REQUESTED_PAGE, 1);
if (isset($_POST['action']))
{
	if (!$context->isLogged()) die(json_encode(array('unauth' => 1)));
	if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

	$action = strtolower(trim($_POST['action']));
	$workingAttachment = (new AttachmentsParser())->getObject($credentials);

	if (!$workingAttachment)
		die(json_encode(array('error'=>1)));

	if ($action === 'like_photo')
	{
		$result = $workingAttachment->like();
		if (!$result)
			die(json_encode(array('error'=>1)));

		die(json_encode(array('response'=>array('state'=>intval($result['state']), 'new_count'=>intval($result['new_count'])))));
	}
}

$found = true;

?>