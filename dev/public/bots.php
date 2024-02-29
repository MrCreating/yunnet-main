<?php

/**
 * Bots management
*/

// handle post actions with bot here.
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Request;
use unt\parsers\AttachmentsParser;
use unt\platform\DataBaseManager;

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);
	switch ($action) 
	{
		case 'create_bot':
			if (count(Bot::getList()) < 30)
			{
                $botName = Request::get()->data["bot_name"];
				$result = Bot::create($botName);
				if (!$result)
					die(json_encode(array('error'=>1)));

				die(json_encode(array('success'=>1)));
			} else 
			{
				die(json_encode(array('error'=>1)));
			}

        case 'get_tokens':
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$tokens_list = [];

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

            $newScreenName = strval(Request::get()->data["new_screen_name"]);

			$result = $bot->setScreenName($newScreenName)->apply();

			if ($result === false)
			{
				die(json_encode(array('error'=>1, 'error_message'=>Context::get()->lang["in_f_3"])));
			}
			if ($result === -1)
			{
				die(json_encode(array('error'=>1, 'error_message'=>Context::get()->lang["in_f_4"])));
			}

			die(json_encode(array('success'=> is_empty(Request::get()->data['new_screen_name']) ? 0 : 1)));
		break;

		case 'set_photo':
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$photo = (new AttachmentsParser())->getObject(strval(Request::get()->data["photo"]));
			if (!$photo)
				die(json_encode(array('error'=>1)));

			$result = false; //update_bot_photo($connection, intval(Request::get()->data['bot_id']), $photo);
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
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$result = false; //update_bot_name($connection, intval(Request::get()->data["bot_id"]), strval(Request::get()->data["new_title"]));
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;
		case 'set_privacy_settings':
			$bot = new Bot(intval(Request::get()->data['bot_id']));
			if (!$bot->valid() || ($bot->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$res = false;// set_privacy_settings($connection, intval($bot->getId()*-1), intval(Request::get()->data["group_id"]), intval(Request::get()->data["new_value"]));
			if (!$res)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;
		default:
		break;
	}
}

?>
