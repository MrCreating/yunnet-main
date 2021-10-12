<?php
$origin = get_page_origin();

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../bin/functions/uploads.php';
require_once __DIR__ . '/../../bin/functions/polls.php';

if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get':
			if (isset($_POST["type"]))
			{
				if (!$context->isLogged() || $context->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

				$attachmentType = strtolower($_POST['type']);
				if ($attachmentType === 'theme' || $attachmentType === 'image')
				{
					$data = get_upload_link($connection, $context->getCurrentUser()->getId(), $origin, $attachmentType);
					if ($data)
						die(json_encode($data));
				}
				if ($attachmentType === 'poll')
				{
					$poll_title           = strval($_POST['poll_title']);
					$anonymous_poll       = boolval(intval($_POST['poll_anonymous']));
					$poll_multi_selection = boolval(intval($_POST['poll_multi_selection']));
					$variant_list         = array();

					$got_variants = json_decode(strval($_POST['poll_answers_list']), true);
					foreach ($got_variants as $index => $answer) 
					{
						if (intval($index) >= 10) break;

						if (is_empty(strval($answer)) || strlen(strval($answer)) > 128) continue;

						$variant_list[] = strval($answer);
					}

					if (is_empty($poll_title) || strlen($poll_title) > 64)
						die(json_encode(array('error' => 1)));

					if (count($variant_list) < 1)
						die(json_encode(array('error' => 1)));

					$poll = create_poll($connection, $poll_title, $context->getCurrentUser()->getId(), $variant_list, $anonymous_poll, $poll_multi_selection, true, 0);
					if ($poll)
					{
						die(json_encode($poll->toArray()));
					}
				}
			}
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

if (strtolower($_REQUEST['action']) === "upload")
{
	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));
	
	$objects = fetch_upload($connection, $_REQUEST['query'], $context->getCurrentUser()->getId());
	if ($objects)
		die(json_encode($objects->toArray()));
}

?>