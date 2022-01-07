<?php

/**
 * Apps system design,
*/
if (!$context->isLogged())
	die(header("Location: /"));

if (!class_exists('App'))
	require __DIR__ . '/../../bin/objects/app.php';
if (!class_exists('Entity'))
	require __DIR__ . '/../../bin/objects/entities.php';

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) 
	{
		case 'create_token':
			if (!function_exists('create_token'))
				require __DIR__ . "/../../bin/functions/auth.php";

			$app_id   = intval(Request::get()->data['app_id']);
			$owner_id = intval(Request::get()->data['bot_id']) > 0 ? (intval(Request::get()->data['bot_id']) * -1) : $context->getCurrentUser()->getId();
			$perms    = explode(',', strval(Request::get()->data['permissions']));

			$permissions = [];
			foreach ($perms as $index => $id)
			{
				if (intval($id) < 1 || intval($id) > 4)
					continue;

				$permissions[] = strval($id);
			}

			$result = create_token($connection, $owner_id, $app_id, $permissions);

			if (!$result) 
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$result)));
		break;

		case 'update_token':
			if (!function_exists('update_token'))
				require __DIR__ . "/../../bin/functions/auth.php";

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

			$token_info = get_token_by_id($connection, $token_id);
			if (!$token_info)
				die(json_encode(array('error'=>1)));

			if ($token_info['owner_id'] < 0)
			{
				$bot = new Bot(intval($token_info['owner_id'])*-1);
				if (!$bot->valid() || $bot->getOwnerId() !== $context->getCurrentUser()->getId())
					die(json_encode(array('error'=>1)));
			} else if ($token_info['owner_id'] !== $context->getCurrentUser()->getId())
			{
				die(json_encode(array('error'=>1)));
			}

			$result = update_token($connection, $token_id, $perms);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'delete_token':
			$token_id = intval(Request::get()->data['token_id']);

			if (!function_exists('get_token_by_id'))
				require __DIR__ . "/../../bin/functions/auth.php";

			$token_info = get_token_by_id($connection, $token_id);
			if (!$token_info)
				die(json_encode(array('error'=>1)));

			if ($token_info['owner_id'] < 0)
			{
				$bot = new Bot(intval($token_info['owner_id'])*-1);
				if (!$bot->valid() || $bot->getOwnerId() !== $context->getCurrentUser()->getId())
					die(json_encode(array('error'=>1)));
			} else if ($token_info['owner_id'] !== $context->getCurrentUser()->getId())
			{
				die(json_encode(array('error'=>1)));
			}

			$result = delete_token($connection, $token_info['id']);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'create_app':
			$app_title = strval(Request::get()->data['app_title']);

			if (!function_exists('create_app'))
				require __DIR__ . "/../../bin/functions/auth.php";

			$app = create_app($connection, $context->getCurrentUser()->getId(), Request::get()->data["app_title"]);
			if (!$app)
				die(json_encode(array('error'=>1)));

			die(json_encode($app->toArray()));
		break;

		case 'get_tokens':
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== $context->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			if (!function_exists('get_tokens_list'))
				require __DIR__ . '/../../bin/functions/auth.php';

			$tokens_list = get_tokens_list($connection, $context->getCurrentUser()->getId(), $app->getId());

			$result = [];
			foreach ($tokens_list as $index => $token) {
				$result[] = [
					'app_id'      => intval($token['app_id']),
					'id'          => intval($token['id']),
					'token'       => strval($token['token']),
					'owner'       => $context->getCurrentUser()->toArray(),
					'permissions' => $token['permissions']
				];
			}

			die(json_encode($result));
		break;

		case 'set_title':
			if (!class_exists('App'))
				require __DIR__ . '/../../bin/objects/apps.php';
			
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== $context->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			if (!$app->setTitle(Request::get()->data["new_title"])->apply())
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>1)));
		break;

		case 'delete_app':
			if (!class_exists('App'))
				require __DIR__ . '/../../bin/objects/apps.php';
			
			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== $context->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>intval($app->delete()))));
		break;

		case 'set_photo':
			$photo = strval(Request::get()->data["photo"]);

			if (!class_exists('AttachmentsParser'))
				require __DIR__ . "/../../bin/objects/attachment.php";
			if (!class_exists('App'))
				require __DIR__ . '/../../bin/objects/apps.php';

			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== $context->getCurrentUser()->getId()))
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
			if (!class_exists('App'))
				require __DIR__ . '/../../bin/objects/apps.php';

			$app = new App(intval(Request::get()->data['app_id']));
			if (!$app->valid() || ($app->getOwnerId() !== $context->getCurrentUser()->getId()))
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