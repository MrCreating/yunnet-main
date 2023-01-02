<?php

use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Entity;
use unt\objects\Post;
use unt\objects\Project;
use unt\objects\Request;
use unt\objects\User;
use unt\parsers\AttachmentsParser;
use unt\platform\DataBaseManager;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) {
		case 'test':
            $this->errors();

			$result = Project::isClosed();

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
				$entity_id = Context::get()->getCurrentUser() === NULL ? 0 : Context::get()->getCurrentUser()->getId();

			if (Context::get()->getCurrentUser() && Context::get()->getCurrentUser()->isBanned()) 
				$entity_id = Context::get()->getCurrentUser()->getId();

			$user = $entity_id > 0 ? User::findById($entity_id) : Bot::findById($entity_id);

			if (!$user) die(json_encode(array("error" => 1)));

			die(json_encode(array("response" => $user->toArray($fields))));
		break;

		case 'get_user_data_by_link':
			$screen_name = substr(strtolower(Request::get()->data["screen_name"]), 1);
			if (Context::get()->getCurrentUser() && Context::get()->getCurrentUser()->isBanned()) 
				$screen_name = 'id' . Context::get()->getCurrentUser()->getId();

			if (is_empty($screen_name)) die(json_encode(array("error"=>1)));

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
			$attachments_list = strval(Request::get()->data['attachments']);
			$wall = intval(Request::get()->data['wall_id']) !== 0 ? intval(Request::get()->data['wall_id']) : Context::get()->getCurrentUser()->getId();

			$result = \unt\functions\wall\create_post(DataBaseManager::getConnection()->getClient(), Context::get()->getCurrentUser()->getId(), $wall, $text, $attachments_list);

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
			$attachments_list = strval(Request::get()->data['attachments']);
			$wall = intval(Request::get()->data['wall_id']);
			$post = intval(Request::get()->data['post_id']);

			$result = \unt\functions\wall\update_post_data(DataBaseManager::getConnection(), Context::get()->getCurrentUser()->getId(), $wall, $post, $text, $attachments_list);

			// if not post updated - show this.
			if (!$result) die(json_encode(array('error' => 1)));

			// fetching the new post data
			$post = Post::findById(intval($wall), intval($post));

			// if not post found - it not be created.
			if (!$post->valid()) die(json_encode(array('error' => 1)));

			// all data is ok!
			die(json_encode(array('response' => $post->toArray())));
		break;

        case 'get_chat_permissions':
        case 'get_my_permissions_level':
        case 'get_chat_by_peer':
            die(json_encode(array('error' => 1)));
		break;

        case 'get_friends':
            if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$user_id = intval(Request::get()->data['user_id']);

            $entity = Entity::findById($user_id);

			if ($entity == NULL || !$entity->canAccessClosed() || $entity->inBlacklist())
				die(json_encode(array('error' => 1)));

			$section = strval(Request::get()->data['section']);
			if (!in_array($section, [
                User::FRIENDS_SECTION_MAIN,
                User::FRIENDS_SECTION_SUBSCRIBERS,
                User::FRIENDS_SECTION_OUTCOMING]
            )) $section = User::FRIENDS_SECTION_MAIN;

			$friends_list = $entity->getFriendsList($section, true);
			$result = array_map(function ($friend) {
                return $friend->toArray('*');
            }, $friends_list);

			die(json_encode($result));
		break;

		case 'get_counters':
			if (Context::get()->getCurrentUser()->isBanned()) die(json_encode(array('error' => 1)));

			$result = [
                'messages'      => 0,
                'notifications' => 0,
                'friends'       => 0
            ];

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