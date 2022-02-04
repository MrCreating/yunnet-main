<?php

require_once __DIR__ . '/../../bin/functions/management.php';
require_once __DIR__ . '/../../bin/functions/wall.php';
require_once __DIR__ . '/../../bin/functions/messages.php';
require_once __DIR__ . '/../../bin/functions/users.php';
require_once __DIR__ . '/../../bin/objects/chats.php';
require_once __DIR__ . '/../../bin/objects/post.php';

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) {
		case 'test':
			$result = is_project_closed();

			if (!$context->isLogged() || $context->getCurrentUser()->getAccessLevel() === 4)
				$result = 0;

			die(json_encode(array('blocked' => $result)));
		break;

		case 'get_language_value':
			$value         = strtolower(Request::get()->data["value"]);
			$languageValue = $context->getLanguage()->{$value};
			if ($value === '*')
			{
				die(json_encode($context->getLanguage()));
			}

			if (!$languageValue) die(json_encode(array('error' => 1)));

			die(json_encode(array('response' => $languageValue)));
		break;
		
		case 'get_user_data':
			$entity_id = intval(Request::get()->data["id"]);
			$fields    = strval(Request::get()->data["fields"]);

			if ($entity_id === 0) 
				$entity_id = intval($context->getCurrentUser() === NULL ? 0 : $context->getCurrentUser()->getId());

			if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned()) 
				$entity_id = intval($context->getCurrentUser()->getId());

			$user = $entity_id > 0 ? new User($entity_id) : new Bot($entity_id*-1);

			if (!$user->valid()) die(json_encode(array("error" => 1)));

			die(json_encode(array("response" => $user->toArray($fields))));
		break;

		case 'get_user_data_by_link':
			$screen_name = strtolower(Request::get()->data["screen_name"]);
			if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned())
				$screen_name = 'id' . $context->getCurrentUser()->getId();

			if (is_empty($screen_name)) die(json_encode(array("error"=>1)));

			$result = resolve_id_by_name(get_database_connection(), $screen_name);
			if (!$result) die(json_encode(array("error"=>1)));

			die(json_encode(array("id"=>intval($result["id"]))));
		break;

		case 'get_attachment_info':
			$attachmentCredentials = strval(trim(Request::get()->data['credentials']));

			$resultedAttachment = (new AttachmentsParser())->getObject($attachmentCredentials);
			if (!$resultedAttachment)
				die(json_encode(array('error' => 1)));

			die(json_encode(array('attachment' => $resultedAttachment->toArray())));
		break;

		default:
		break;
	}

	if (!Context::get()->isLogged()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_settings':
			$settings = Context::get()->getCurrentUser()->getSettings()->toArray();

			die(json_encode($settings));
		break;

		case 'publish_post':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$text = strval(Request::get()->data['text']);
			$atts = strval(Request::get()->data['attachments']);
			$wall = intval(Request::get()->data['wall_id']) !== 0 ? intval(Request::get()->data['wall_id']) : $context->getCurrentUser()->getId();

			$result = create_post($connection, $context->getCurrentUser()->getId(), $wall, $text, $atts);

			// if not post created - show this.
			if (!$result) die(json_encode(array('error'=>1)));

			// fetching the new post data
			$post = Post::findById(intval($wall), intval($result));

			// if not post found - it not be created.
			if (!$post->valid()) die(json_encode(array('error'=>1)));

			// all data is ok!
			die(json_encode(array('response'=>$post->toArray())));
		break;

		case 'edit_post':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$text = strval(Request::get()->data['text']);
			$atts = strval(Request::get()->data['attachments']);
			$wall = intval(Request::get()->data['wall_id']);
			$post = intval(Request::get()->data['post_id']);

			$result = update_post_data($context->getConnection(), $context->getCurrentUser()->getId(), $wall, $post, $text, $atts);

			// if not post updated - show this.
			if (!$result) die(json_encode(array('error' => 1)));

			// fetching the new post data
			$post = Post::findById(intval($wall), intval($post));

			// if not post found - it not be created.
			if (!$post->valid()) die(json_encode(array('error' => 1)));

			// all data is ok!
			die(json_encode(array('response' => $post->toArray())));
		break;

		case 'get_chat_by_peer':
			$sel = strval(Request::get()->data['peer_id']);
			$chat_data = parse_id_from_string($sel);

			// if format is invalid
			if (!$chat_data) die(json_encode(array('error' => 1)));

			$uid = intval(get_uid_by_lid($context->getConnection(), $chat_data['chat_id'], $chat_data['is_bot'], $context->getCurrentUser()->getId()));
			$dialog = get_chat_data_by_uid($context->getConnection(), $uid, $context->getCurrentUser()->getId(), $chat_data);

			if (!$dialog) die(json_encode(array('error' => 1)));

			die(json_encode($dialog));
		break;

		case 'get_chat_permissions':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$chat_data = parse_id_from_string(Request::get()->data['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error' => 1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error' => 1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error' => 1)));

			$members = $chat->getMembers(true);
			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me['flags']['is_kicked']) die(json_encode(array('error' => 1)));

			$permissions = $chat->getPermissions();
			die(json_encode($permissions->getAll()));
		break;

		case 'get_my_permissions_level':
			$chat_data = parse_id_from_string(Request::get()->data['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error' => 1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error' => 1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error' => 1)));

			$members = $chat->getMembers(true);
			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me['flags']['is_kicked']) die(json_encode(array('error'=>1)));

			die(json_encode(array('level' => intval($me["flags"]["level"]))));
		break;

		case 'get_friends':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$user_id = intval(Request::get()->data['user_id']);

			if (!user_exists($connection, $user_id) || !can_access_closed($connection, $context->getCurrentUser()->getId(), $user_id) || in_blacklist($connection, $user_id, $context->getCurrentUser()->getId()))
				die(json_encode(array('error'=>1)));

			$section = strval(Request::get()->data['section']);
			if (!in_array($section, ['friends', 'subscribers', 'outcoming'])) $section = 'friends';

			$friends_list = get_friends_list($connection, $user_id, $section, true);
			$result = [];

			foreach ($friends_list as $key => $friend) {
				$result[] = $friend->toArray('*');
			}

			die(json_encode($result));
		break;

		case 'get_counters':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$result = get_counters($connection, $context->getCurrentUser()->getId());

			die(json_encode($result));
		break;

		case 'set_status':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));
			
			$requested_status = trim(strval(Request::get()->data['new_status']));
			if (strlen($requested_status) > 128)
				die(json_decode(array('error' => 1)));

			$result = Context::get()->getCurrentUser()->edit()->setStatus($requested_status);
			if ($result)
				die(json_encode(array(
					'success' => 1,
					'status'  => $requested_status
				)));
		break;
		
		default:
		break;
	}

	if (!$dev) 
		die(json_encode(array('error' => 1)));
}

if (!$dev) 
	die(json_encode(array('flex' => 1)));
?>