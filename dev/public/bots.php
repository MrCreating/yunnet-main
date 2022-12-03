<?php

/**
 * Bots management
*/

// connect bots module
require __DIR__ . "/../../bin/functions/bots.php";

// handle post actions with bot here.
if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);
	switch ($action) 
	{
		case 'create_bot':
			if (!function_exists('create_bot'))
				require __DIR__ . "/../../bin/functions/bots.php";

			if (count(get_bots_list($connection, Context::get()->getCurrentUser()->getId())) < 30)
			{
				$result = create_bot($connection, Context::get()->getCurrentUser()->getId(), Request::get()->data["bot_name"]);
				if (!$result)
					die(json_encode(array('error'=>1)));

				die(json_encode(array('success'=>1)));
			} else 
			{
				die(json_encode(array('error'=>1)));
			}
		break;

		case 'get_tokens':
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			if (!function_exists('get_tokens_list'))
				require __DIR__ . '/../../bin/functions/auth.php';

			$tokens_list = get_tokens_list($connection, $bot->getId() * -1);

			$result = [];
			foreach ($tokens_list as $index => $token) {
				$result[] = [
					'app_id'      => intval($token['app_id']),
					'id'          => intval($token['id']),
					'token'       => strval($token['token']),
					'owner'       => $bot->toArray(),
					'permissions' => $token['permissions']
				];
			}

			die(json_encode($result));
		break;

		case 'change_screen_name':
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			if (!function_exists('update_screen_name'))
				require __DIR__ . '/../../bin/functions/alsettings.php';

			$result = update_screen_name($connection, intval($bot->getId())*-1, (unt\functions\is_empty(Request::get()->data["new_screen_name"]) ? NULL : strval(Request::get()->data["new_screen_name"])));

			if ($result === false)
			{
				die(json_encode(array('error'=>1, 'error_message'=>Context::get()->lang["in_f_3"])));
			}
			if ($result === -1)
			{
				die(json_encode(array('error'=>1, 'error_message'=>Context::get()->lang["in_f_4"])));
			}

			die(json_encode(array('success'=>unt\functions\is_empty(Request::get()->data['new_screen_name']) ? 0 : 1)));
		break;

		case 'set_photo':
			if (!class_exists('AttachmentsParser'))
				require __DIR__ . "/../../bin/objects/Attachment.php";
			if (!class_exists('Bot'))
				require __DIR__ . '/../../bin/objects/entities.php';

			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$photo = (new AttachmentsParser())->getObject(strval(Request::get()->data["photo"]));
			if (!$photo)
				die(json_encode(array('error'=>1)));

			if (!function_exists('update_bot_photo'))
				require __DIR__ . '/../../bin/functions/bots.php';

			$result = update_bot_photo($connection, intval(Request::get()->data['bot_id']), $photo);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('state'=>1)));
		break;
		case 'delete_photo':
			if (!class_exists('Bot'))
				require __DIR__ . '/../../bin/objects/entities.php';

			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			die(json_encode(array('state'=>intval(DataBaseManager::getConnection()->prepare("UPDATE bots.info SET photo_path = NULL WHERE id = ? LIMIT 1;")->execute([intval(Request::get()->data['bot_id'])])))));
		break;
		case 'set_title':
			if (!function_exists('update_bot_name'))
				require __DIR__ . '/../../bin/functions/bots.php';
			if (!class_exists('Bot'))
				require __DIR__ . '/../../bin/objects/entities.php';

			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$result = update_bot_name($connection, intval(Request::get()->data["bot_id"]), strval(Request::get()->data["new_title"]));
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;
		case 'set_privacy_settings':
			if (!function_exists('set_privacy_settings'))
				require __DIR__ . '/../../bin/functions/users.php';
			if (!class_exists('Bot'))
				require __DIR__ . '/../../bin/objects/entities.php';

			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$res = set_privacy_settings($connection, intval($bot->getId()*-1), intval(Request::get()->data["group_id"]), intval(Request::get()->data["new_value"]));
			if (!$res)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;
		default:
		break;
	}
}

?>