<?php
$origin = unt\functions\get_page_origin();

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');;

require_once __DIR__ . '/../../bin/functions/uploads.php';
require_once __DIR__ . '/../../bin/objects/Poll.php';

if (isset(Request::get()->data['action']))
{
	if (!$context->allowToUseUnt()) 
		die(json_encode(array('error' => 1)));

	$action = strtolower(Request::get()->data['action']);
	
	switch ($action) {
		case 'get':
			if (isset(Request::get()->data["type"]))
			{
				$attachmentType = strtolower(Request::get()->data['type']);

				if ($attachmentType === 'theme' || $attachmentType === 'image')
				{
					$data = get_upload_link($connection, $context->getCurrentUser()->getId(), $origin, $attachmentType);
					if ($data)
						die(json_encode($data));
				}
				if ($attachmentType === 'poll')
				{
					$poll_title           = strval(Request::get()->data['poll_title']);
					$anonymous_poll       = boolval(intval(Request::get()->data['poll_anonymous']));
					$poll_multi_selection = boolval(intval(Request::get()->data['poll_multi_selection']));
					$variant_list         = array();

					$got_variants = json_decode(strval(Request::get()->data['poll_answers_list']), true);
					foreach ($got_variants as $index => $answer) 
					{
						if (intval($index) >= 10) break;

						if (unt\functions\is_empty(strval($answer)) || strlen(strval($answer)) > 128) continue;

						$variant_list[] = strval($answer);
					}

					if (unt\functions\is_empty($poll_title) || strlen($poll_title) > 64)
						die(json_encode(array('error' => 1)));

					if (count($variant_list) < 1)
						die(json_encode(array('error' => 1)));

					$poll = Poll::create($poll_title, $variant_list, 0, $anonymous_poll, $poll_multi_selection, true);
					if ($poll)
					{
						die(json_encode($poll->toArray()));
					}
				}
			}
		break;

		case "upload":
			$objects = fetch_upload($connection, Request::get()->data['query'], $context->getCurrentUser()->getId());
			if ($objects)
				die(json_encode($objects->toArray()));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>