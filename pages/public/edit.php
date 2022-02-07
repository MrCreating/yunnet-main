<?php

require_once __DIR__ . "/../../bin/functions/users.php";

/**
 * Profile editing page and actions.
*/

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'save':
			if ($context->getCurrentUser()->getFirstName() !== Request::get()->data["first_name"] && isset(Request::get()->data['first_name']))
			{
				$changed = Context::get()->getCurrentUser()->edit()->setFirstName(Request::get()->data["first_name"]);
				if ($changed !== false && $changed !== true)
				{
					switch ($changed)
					{
						case -1:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_fn)));
						break;
						case -2:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_fn)));
						break;
						case -3:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->need_all_data)));
						break;
					}
				}

				die(json_encode(array('response'=>1)));
			}
			if ($context->getCurrentUser()->getFirstName() !== Request::get()->data["last_name"] && isset(Request::get()->data['last_name']))
			{
				$changed = Context::get()->getCurrentUser()->edit()->setLastName(Request::get()->data["last_name"]);
				if ($changed !== false && $changed !== true)
				{
					switch ($changed)
					{
						case -1:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_ln)));
						break;
						case -2:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_ln)));
						break;
						case -3:
							die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->need_all_data)));
						break;
					}
				}

				die(json_encode(array('response'=>1)));
			}

			if ($context->getCurrentUser()->getScreenName() !== Request::get()->data["screen_name"] && isset(Request::get()->data['screen_name']))
			{
				$result = Context::get()->getCurrentUser()->edit()->setScreenName(is_empty(Request::get()->data["screen_name"]) ? NULL : Request::get()->data["screen_name"]);
				if ($result === 0)
				{
					die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->in_f_3)));
				}
				if ($result === -1)
				{
					die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->in_f_4)));
				}

				die(json_encode(array('response'=>1)));
			}

			if (isset(Request::get()->data['photo']))
			{
				$attachment = (new AttachmentsParser())->getObject(Request::get()->data['photo']);
				
				$result = Context::get()->getCurrentUser()->edit()->setPhoto($attachment);
				if ($result === false)
					die(json_encode(array('error' => 1)));
				if ($result === true)
					die(json_encode(array('response' => 1)));
				if ($result instanceof Photo)
					die(json_encode($result->toArray()));
			}

			die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->in_f_2)));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}

?>
