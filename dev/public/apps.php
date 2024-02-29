<?php

/**
 * Apps system design,
*/

use unt\objects\App;
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Request;
use unt\parsers\AttachmentsParser;

if (!Context::get()->isLogged())
	die(header("Location: /"));

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) 
	{
		case 'create_token':
			$app_id   = intval(Request::get()->data['app_id']);
			$owner_id = intval(Request::get()->data['bot_id']) > 0 ? (intval(Request::get()->data['bot_id']) * -1) : Context::get()->getCurrentUser()->getId();
			$perms    = explode(',', strval(Request::get()->data['permissions']));

			$permissions = [];
			foreach ($perms as $index => $id)
			{
				if (intval($id) < 1 || intval($id) > 4)
					continue;

				$permissions[] = strval($id);
			}

			$result = false; //create_token($connection, $owner_id, $app_id, $permissions);

			if (!$result) 
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$result)));
		break;

		case 'update_token':
			$perms = explode(',', Request::get()->data["permissions"]);
			$token_id = intval(Request::get()->data['token_id']);

			if (count($perms) > 4)
				die(json_encode(array('error'=>1)));

			$permissions = [];
			foreach ($perms as $index => $id)
			{
				if (intval($id) < 1 || intval($id) > 4)
					continue;

				$permissions[] = strval($id);
			}

			$perms = implode(',', $permissions);

			$token_info = false; //get_token_by_id($connection, $token_id);
			if (!$token_info)
				die(json_encode(array('error'=>1)));

			if ($token_info['owner_id'] < 0)
			{
				$bot = new Bot(intval($token_info['owner_id'])*-1);
				if (!$bot->valid() || $bot->getOwnerId() !== Context::get()->getCurrentUser()->getId())
					die(json_encode(array('error'=>1)));
			} else if ($token_info['owner_id'] !== Context::get()->getCurrentUser()->getId())
			{
				die(json_encode(array('error'=>1)));
			}

			$result = false; //update_token($connection, $token_id, $perms);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'delete_token':
			$token_id = intval(Request::get()->data['token_id']);

			$token_info = false; //get_token_by_id($connection, $token_id);
			if (!$token_info)
				die(json_encode(array('error'=>1)));

			if ($token_info['owner_id'] < 0)
			{
				$bot = new Bot(intval($token_info['owner_id'])*-1);
				if (!$bot->valid() || $bot->getOwnerId() !== Context::get()->getCurrentUser()->getId())
					die(json_encode(array('error'=>1)));
			} else if ($token_info['owner_id'] !== Context::get()->getCurrentUser()->getId())
			{
				die(json_encode(array('error'=>1)));
			}

			$result = false; // delete_token($connection, $token_info['id']);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'create_app':
			$app_title = strval(Request::get()->data['app_title']);

			$app = false; //create_app($connection, Context::get()->getCurrentUser()->getId(), Request::get()->data["app_title"]);
			if (!$app)
				die(json_encode(array('error'=>1)));

			die(json_encode($app->toArray()));
		break;

		case 'get_tokens':
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$tokens_list = $app->getTokensList();

			$result = [];
			foreach ($tokens_list as $index => $token) {
				$result[] = [
					'app_id'      => intval($token['app_id']),
					'id'          => intval($token['id']),
					'token'       => strval($token['token']),
					'owner'       => Context::get()->getCurrentUser()->toArray(),
					'permissions' => $token['permissions']
				];
			}

			die(json_encode($result));
		break;

		case 'set_title':
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			if (!$app->setTitle(Request::get()->data["new_title"])->apply())
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'delete_app':
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>intval($app->delete()))));
		break;

		case 'set_photo':
			$photo = strval(Request::get()->data["photo"]);

			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$photo = (new AttachmentsParser())->getObject($photo);
			if (!$photo)
				die(json_encode(array('error'=>1)));

			$result = $app->setPhoto($photo)->apply();
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'delete_photo':
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== Context::get()->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$result = $app->setPhoto(NULL)->apply();
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;
		
		default:
		break;
	}
}

?>
