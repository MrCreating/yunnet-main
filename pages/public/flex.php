<?php

require_once __DIR__ . '/../../bin/functions/management.php';
require_once __DIR__ . '/../../bin/functions/wall.php';
require_once __DIR__ . '/../../bin/functions/messages.php';
require_once __DIR__ . '/../../bin/functions/users.php';
require_once __DIR__ . '/../../bin/objects/Chat.php';
require_once __DIR__ . '/../../bin/objects/Dialog.php';
require_once __DIR__ . '/../../bin/objects/Conversation.php';
require_once __DIR__ . '/../../bin/objects/Post.php';

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) {
		case 'test':
			$result = is_project_closed();

			if (!Context::get()->isLogged() || Context::get()->getCurrentUser()->getAccessLevel() === 4)
				$result = 0;

			die(json_encode(array('blocked' => $result)));
		break;

		case 'get_language_value':
			$value         = strtolower(Request::get()->data["value"]);
			$languageValue = Context::get()->getLanguage()->{$value};
			if ($value === '*')
			{
				die(json_encode(Context::get()->getLanguage()));
			}

			if (!$languageValue) die(json_encode(array('error' => 1)));

			die(json_encode(array('response' => $languageValue)));
		break;
		
		case 'get_user_data':
			$entity_id = intval(Request::get()->data["id"]);
			$fields    = strval(Request::get()->data["fields"]);

			if ($entity_id === 0) 
				$entity_id = Context::get()->getCurrentUser() === NULL ? 0 : $context->getCurrentUser()->getId();

			if (Context::get()->getCurrentUser() && Context::get()->getCurrentUser()->isBanned()) 
				$entity_id = Context::get()->getCurrentUser()->getId();

			$user = Entity::findById($entity_id);

			if (!$user) die(json_encode(array("error" => 1)));

			die(json_encode(array("response" => $user->toArray($fields))));
		break;

		case 'get_user_data_by_link':
			$screen_name = substr(strtolower(Request::get()->data["screen_name"]), 1);
			if (Context::get()->getCurrentUser() && Context::get()->getCurrentUser()->isBanned()) 
				$screen_name = 'id' . Context::get()->getCurrentUser()->getId();

			if (unt\functions\is_empty($screen_name)) die(json_encode(array("error"=>1)));

			$result = Entity::findByScreenName($screen_name);
			if (!$result) die(json_encode(array("error"=>1)));

			die(json_encode(array("id"=>intval($result->getId() > 0 ? $result->getId() : ($result->getId() * -1)))));
		break;

		case 'get_attachment_info':
			$attachmentCredentials = trim(Request::get()->data['credentials']);

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

			$result = create_post(DataBaseManager::getConnection()->getClient(), Context::get()->getCurrentUser()->getId(), $wall, $text, $atts);

			// if not post created - show this.
			if (!$result) die(json_encode(array('error'=>1)));

			// fetching the new post data
			$post = Post::findById($wall, intval($result));

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

			$result = update_post_data(Context::get()->getConnection(), Context::get()->getCurrentUser()->getId(), $wall, $post, $text, $atts);

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
			$dialog = Chat::findById(Request::get()->data['peer_id']);

			if (!$dialog) die(json_encode(array('error' => 1)));

			die(json_encode($dialog->toArray()));
		break;

		case 'get_chat_permissions':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$chat = new Conversation(Request::get()->data['peer_id']);

			if (!$chat->valid() || $chat->isKicked())
				die(json_encode(array('error' => 1)));

			die(json_encode($chat->getPermissions()->toArray()));
		break;

		case 'get_my_permissions_level':
			$chat = new Conversation(Request::get()->data['peer_id']);

			if (!$chat->valid() || $chat->isKicked())
				die(json_encode(array('error' => 1)));

			die(json_encode(array('level' => $chat->getAccessLevel())));
		break;

		case 'get_friends':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$user_id = intval(Request::get()->data['user_id']);

			if (!Entity::findById($user_id) == NULL || !can_access_closed(DataBaseManager::getConnection()->getClient(), $context->getCurrentUser()->getId(), $user_id) || in_blacklist($connection, $user_id, $context->getCurrentUser()->getId()))
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

			$result = get_counters(DataBaseManager::getConnection()->getClient(), Context::get()->getCurrentUser()->getId());

			die(json_encode($result));
		break;

		case 'set_status':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));
			
			$requested_status = trim(strval(Request::get()->data['new_status']));
			if (strlen($requested_status) > 128)
				die(json_encode(array('error' => 1)));

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