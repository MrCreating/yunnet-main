<?php

require_once __DIR__ . '/../../bin/functions/management.php';

/**
 * ADMIN PANEL!!
*/

$current_user_level = $context->getCurrentUser()->getAccessLevel();

if (!($current_user_level < 1 || !$context->allowToUseUnt()))
{
	if (isset($_POST['action']))
	{
		$action  = strtolower(strval($_POST['action']));
		$user_id = intval($_POST['user_id']) === 0 ? intval($_SESSION['user_id']) : intval($_POST['user_id']);

		$user = ($user_id > 0) ? (new User($user_id)) : (new Bot($user_id * -1));
		if (!$user->valid())
			die(json_encode(array('error' => 1)));

		switch ($action)
		{
			case 'show_project_files':
				if ($current_user_level < 4) die(json_encode(array('error' => 1)));

				$filePath = strval($_POST['file_path']);

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
				if ($current_user_level >= 1 && $user->getAccessLevel() <= $current_user_level)
				{
					$result = intval($connection->prepare("UPDATE ".($user->type === "bot" ? "bots.info" : "users.info")." SET is_verified = ? WHERE id = ? LIMIT 1;")->execute([intval(!intval($user->isVerified())), intval($user->getId())]));

					if ($result)
						die(json_encode(array('state'=>!intval($user->isVerified()))));
				}
			break;

			case 'toggle_online_show_state':
				if ($current_user_level >= 4 && $user->getAccessLevel() <= $current_user_level)
				{
					if ($user->type !== "bot")
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

					if ($user->getFirstName() !== $_POST["first_name"] && isset($_POST['first_name']))
					{
						$changed = update_user_data($connection, $user_id, "first_name", $_POST["first_name"]);
						if ($changed !== false && $changed !== true)
						{
							switch ($changed)
							{
								case -1:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['bad_data_fn'])));
								break;
								case -2:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['bad_data_fn'])));
								break;
								case -3:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['need_all_data'])));
								break;
							}
						}

						die(json_encode(array('response'=>1)));
					}
					if ($user->getLastName() !== $_POST["last_name"] && isset($_POST['last_name']))
					{
						$changed = update_user_data($connection, $user_id, "last_name", $_POST["last_name"]);
						if ($changed !== false && $changed !== true)
						{
							switch ($changed)
							{
								case -1:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['bad_data_ln'])));
								break;
								case -2:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['bad_data_ln'])));
								break;
								case -3:
									die(json_encode(array('error'=>1, 'message'=>$context->lang['need_all_data'])));
								break;
							}
						}

						die(json_encode(array('response'=>1)));
					}

					if ($user->getScreenName() !== $_POST["screen_name"] && isset($_POST['screen_name']))
					{
						if (!function_exists('update_screen_name'))
							require __DIR__ . '/../../bin/functions/alsettings.php';

						$result = update_screen_name($connection, $user_id, (is_empty($_POST["screen_name"]) ? NULL : $_POST["screen_name"]));
						if ($result === false)
						{
							die(json_encode(array('error'=>1, 'message'=>$context->lang["in_f_3"])));
						}
						if ($result === -1)
						{
							die(json_encode(array('error'=>1, 'message'=>$context->lang["in_f_4"])));
						}

						die(json_encode(array('response'=>1)));
					}

					if (isset($_POST['photo']))
					{
						$attachment_data = strval($_POST['photo']);
						if (is_empty($attachment_data))
						{
							$result = delete_user_photo($connection, $user_id);
							if ($result) die(json_encode(array('response'=>1)));
						} else
						{
							$result = update_user_photo($connection, $user_id, $attachment_data);
							if ($result) die(json_encode($result->toArray()));

							die(json_encode(array('error'=>1)));
						}
					}

					die(json_encode(array('error'=>1, 'message'=>$context->lang['in_f_2'])));
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

		die(json_encode(array('error'=>1)));
	}
}

?>