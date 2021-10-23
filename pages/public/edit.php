<?php

require_once __DIR__ . "/../../bin/functions/users.php";

/**
 * Profile editing page and actions.
*/

if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'save':
			if ($context->getCurrentUser()->getFirstName() !== $_POST["first_name"] && isset($_POST['first_name']))
			{
				$changed = update_user_data($connection, $context->getCurrentUser()->getId(), "first_name", $_POST["first_name"]);
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
			if ($context->getCurrentUser()->getFirstName() !== $_POST["last_name"] && isset($_POST['last_name']))
			{
				$changed = update_user_data($connection, $context->getCurrentUser()->getId(), "last_name", $_POST["last_name"]);
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

			if ($context->getCurrentUser()->getScreenName() !== $_POST["screen_name"] && isset($_POST['screen_name']))
			{
				$result = Context::get()->getCurrentUser()->edit()->setScreenName(is_empty($_POST["screen_name"]) ? NULL : $_POST["screen_name"]);
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

			if (isset($_POST['photo']))
			{
				$attachment = (new AttachmentsParser())->getObject($_POST['photo']);
				
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
