<?php

use unt\objects\Context;
use unt\objects\Poll;
use unt\objects\Request;
use unt\objects\UploadManager;

$origin = \unt\objects\Project::getOrigin();

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');

if (isset(Request::get()->data['action']))
{
	if (!Context::get()->allowToUseUnt()) 
		die(json_encode(array('error' => 1)));

	$action = strtolower(Request::get()->data['action']);
	
	switch ($action) {
		case 'get':
			if (isset(Request::get()->data["type"]))
			{
				$attachmentType = strtolower(Request::get()->data['type']);

                // TODO: убрать костыль из js тоже
                if ($attachmentType === 'image')
                    $attachmentType = 'photo';

				if ($attachmentType === \unt\objects\Theme::ATTACHMENT_TYPE || $attachmentType === \unt\objects\Photo::ATTACHMENT_TYPE)
				{
					$data = UploadManager::getLink($attachmentType);
					if ($data)
						die(json_encode($data));
				}
				if ($attachmentType === Poll::ATTACHMENT_TYPE)
				{
					$poll_title           = strval(Request::get()->data['poll_title']);
					$anonymous_poll       = boolval(intval(Request::get()->data['poll_anonymous']));
					$poll_multi_selection = boolval(intval(Request::get()->data['poll_multi_selection']));
					$variant_list         = [];

					$got_variants = json_decode(strval(Request::get()->data['poll_answers_list']), true);
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

					$poll = Poll::create($poll_title, $variant_list, 0, $anonymous_poll, $poll_multi_selection, true);
					if ($poll)
					{
						die(json_encode($poll->toArray()));
					}
				}
			}
		break;

		case "upload":
            $this->errors();
            header('Content-Type: text/html');

            try {
                $upload = new UploadManager(Request::get()->data['query']);
                if (!isset($upload->getUploadedAttachments()[0]))
                    throw new Exception('Failed to upload', 1);

                die(json_encode($upload->getUploadedAttachments()[0]->toArray()));
            } catch (\Exception $e) {
                die(json_encode(array('error' => $e->getCode(), 'message' => $e->getMessage())));
            }
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>