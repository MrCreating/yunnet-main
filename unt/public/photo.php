<?php

require_once __DIR__ . '/../../bin/parsers/AttachmentsParser.php';

$credentials = substr(REQUESTED_PAGE, 1);

if (isset(Request::get()->data['action']))
{
	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	$action = strtolower(trim(Request::get()->data['action']));
	$workingAttachment = (new AttachmentsParser())->getObject($credentials);

	if (!$workingAttachment)
		die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'like_photo':
			$result = $workingAttachment->like();
			if (!$result)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('response' => array('state' => intval($result['state']), 'new_count'=>intval($result['new_count'])))));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>