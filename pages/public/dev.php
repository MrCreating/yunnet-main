<?php

require_once __DIR__ . '/../../bin/functions/management.php';

/**
 * ADMIN PANEL!!
*/

$context = Context::get();
$current_user_level = $context->getCurrentUser()->getAccessLevel();

if (!($current_user_level < 1 || !$context->allowToUseUnt()))
{
	if (isset(Request::get()->data['action']))
	{
		$action  = strtolower(strval(Request::get()->data['action']));
		$user_id = intval(Request::get()->data['user_id']) === 0 ? intval($_SESSION['user_id']) : intval(Request::get()->data['user_id']);

		$user = Entity::findById($user_id);
		if (!$user->valid())
			die(json_encode(array('error' => 1)));

        $connection = DataBaseManager::getConnection();

		switch ($action)
		{
			case 'show_project_files':
				if ($current_user_level < 4) die(json_encode(array('error' => 1)));

				$filePath = strval(Request::get()->data['file_path']);

				$directory = opendir(__DIR__ . '/../..' . $filePath);
				$response = [];

				$i = 0;
				while (false !== ($file = readdir($directory)))
				{
					if ($file === '.' || $file === '..') continue;

					$i++;

					$response[] = [
						'name' => $file,
						'type' => is_dir(__DIR__ . '/../..' . $filePath . $file) ? "directory" : "file",
						'id'   => $i
					];
				}

				closedir($directory);

				die(json_encode(array('response' => $response)));
			break;

			case 'toggle_ban_state':
				if ($current_user_level >= 3  && $user->getAccessLevel() < $current_user_level)
				{
					if ($user->getAccessLevel() !== 4)
					{
						if (ban($connection, $user_id))
						{
							die(json_encode(array('state' => !intval($user->isBanned()))));
						}
					}
				}
			break;

			case 'toggle_verification_state':
				if ($user->getAccessLevel() <= $current_user_level)
				{
					$result = intval($connection->prepare("UPDATE ".($user->getType() === "bot" ? "bots.info" : "users.info")." SET is_verified = ? WHERE id = ? LIMIT 1;")->execute([intval(!intval($user->isVerified())), intval($user->getId())]));

					if ($result)
						die(json_encode(array('state'=>!intval($user->isVerified()))));
				}
			break;

			case 'toggle_online_show_state':
				if ($current_user_level >= 4 && $user->getAccessLevel() <= $current_user_level)
				{
					if ($user->getType() !== "bot")
					{
						$result = intval($connection->prepare("UPDATE users.info SET online_hidden = ? WHERE id = ? LIMIT 1;")->execute([intval(!intval($user->getOnline()->isOnlineHidden)), intval($user->getId())]));

						if ($result)
							die(json_encode(array('state'=>!intval($user->getOnline()->isOnlineHidden))));
					}
				}
			break;

			case 'edit_user':
				if ($current_user_level >= 2 && $user->getAccessLevel() <= $current_user_level)
				{
					if (!function_exists("update_user_data"))
						require __DIR__ . "/../../bin/functions/users.php";

					if ($user->getFirstName() !== Request::get()->data["first_name"] && isset(Request::get()->data['first_name']))
					{
						$changed = $user->edit()->setFirstName(Request::get()->data["first_name"]);
						if ($changed !== false && $changed !== true)
						{
							switch ($changed)
							{
                                case -2:
                                case -1:
									die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_fn)));
								break;
                                case -3:
									die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->need_all_data)));
								break;
							}
						}

						die(json_encode(array('response'=>1)));
					}
					if ($user->getLastName() !== Request::get()->data["last_name"] && isset(Request::get()->data['last_name']))
					{
						$changed = $user->edit()->setLastName(Request::get()->data["last_name"]);
						if ($changed !== false && $changed !== true)
						{
							switch ($changed)
							{
                                case -2:
                                case -1:
									die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->bad_data_ln)));
								break;
                                case -3:
									die(json_encode(array('error'=>1, 'message'=>$context->getLanguage()->need_all_data)));
								break;
							}
						}

						die(json_encode(array('response'=>1)));
					}

					if ($user->getScreenName() !== Request::get()->data["screen_name"] && isset(Request::get()->data['screen_name']))
					{
						$result = $user->edit()->setScreenName(is_empty(Request::get()->data["screen_name"]) ? NULL : Request::get()->data["screen_name"]);
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
						$attachment_data = strval(Request::get()->data['photo']);
						if (is_empty($attachment_data))
						{
							$result = $user->setPhoto()->apply();
							if ($result) die(json_encode(array('response'=>1)));
						} else
						{
                            $attachment = (new AttachmentsParser())->getObject($attachment_data);
                            if ($attachment instanceof Photo) {
                                $result = $user->setPhoto($attachment)->apply();

                                if ($result) die(json_encode($result->toArray()));
                            }

							die(json_encode(array('error' => 1)));
						}
					}

					die(json_encode(array('error' => 1, 'message' => $context->getLanguage()->in_f_2)));
				}
			break;

			case 'get_project_settings':
				if ($current_user_level >= 4)
				{
					$closed_project = is_project_closed();
					$closed_register = is_register_closed();

					die(json_encode(array('closed_project' => intval($closed_project), 'closed_register' => intval($closed_register))));
				}
			break;

			case 'toggle_project_close':
				if ($current_user_level >= 4)
				{
					die(json_encode(array('response' => intval(toggle_project_close()))));
				}
			break;

			case 'toggle_register_close':
				if ($current_user_level >= 4)
				{
					die(json_encode(array('response' => intval(toggle_register_close()))));
				}
			break;

			case 'delete_user':
				if ($current_user_level >= 3 && $user->getAccessLevel() <= $current_user_level)
				{
					die(json_encode(array('success'=>delete_user($connection, $user_id))));
				}
			break;

			default:
			break;
		}

		die(json_encode(array('error' => 1)));
	}
}

?>