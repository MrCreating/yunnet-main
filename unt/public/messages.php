<?php

use unt\objects\Chat;
use unt\objects\Context;
use unt\objects\Conversation;
use unt\objects\Message;
use unt\objects\Photo;
use unt\objects\Request;
use unt\parsers\AttachmentsParser;
use unt\platform\Data;

if (isset(Request::get()->data['action']))
{
    $action = strtolower(Request::get()->data['action']);

    switch ($action) {
        case 'get_chat_info_by_link':
            die(json_encode(array('error' => 3)));

        default:
            break;
    }

    if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

    if (isset(Request::get()->data['peer_id']) || isset(Request::get()->data['chat_id']))
    {
        $peer_id = trim(strtolower(isset(Request::get()->data['chat_id']) ? strval(Request::get()->data['chat_id']) : strval(Request::get()->data['peer_id'])));
        $chat    = Chat::findById($peer_id);

        if (!$chat || !$chat->valid())
            die(json_encode(array('error' => 1)));

        switch ($action) {
            case 'send_message':
                $result = $chat->sendMessage(strval(Request::get()->data['text']), strval(Request::get()->data['attachments']), strval(Request::get()->data['fwd']), strval(Request::get()->data['payload']));
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('id' => $result)));

            case 'toggle_my_state':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $result = $chat->isLeaved() ? $chat->addUser(intval($_SESSION['user_id'])) : $chat->removeUser(intval($_SESSION['user_id']));
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('result' => 1)));

            case 'read_chat':
                die(json_encode(array('success' => intval($chat->read()))));

            case 'clear':
                $chat->clear();

                die(json_encode(array()));

            case 'set_user_level':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $new_level = intval(Request::get()->data['new_level']);
                $user_id   = intval(Request::get()->data['user_id']);

                $result = $chat->setUserPermissionsLevel($user_id, $new_level);
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));
                break;

            case 'get_members':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                die(json_encode(array_map(function (Data $member) {
                    $object = $member->entity && $member->entity->valid() ? $member->entity->toArray() : ['is_deleted' => 1];

                    if (!$member->entity || !$member->entity->valid())
                    {
                        if ($member->user_id > 0)
                            $object['user_id'] = $member->user_id;
                        else
                            $object['bot_id'] = $member->user_id;
                    }

                    $object['chat_info'] = [
                        'access_level' => $member->access_level,
                        'invited_by'   => $member->invited_by,
                        'is_muted'     => $member->is_muted
                    ];

                    return $object;
                }, $chat->getMembers(true))));

            case 'get_messages':
                die(json_encode(array('list' => array_map(function (Message $message) { return $message->toArray(); }, $chat->getMessages(intval(Request::get()->data['count']) !== 0 ? intval(Request::get()->data['count']) : 100, intval(Request::get()->data['offset']) !== 0 ? intval(Request::get()->data['offset']) : 0)))));

            case 'toggle_notifications':
                die(json_encode(array('response' => intval($chat->setNotificationsEnabled()))));

            case 'toggle_pinned_messages':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => intval($chat->setPinnedMessageShown()))));

            case 'set_chat_title':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $result = $chat->setTitle(strval(Request::get()->data['new_title']));
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => 1)));

            case 'update_chat_photo':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                /** @var Photo $photo */
                $photo = (new AttachmentsParser())->getObject(Request::get()->data['photo']);
                if (!$photo)
                    die(json_encode(array('error' => 1)));

                $result = $chat->setPhoto($photo);
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => 1)));

            case 'delete_chat_photo':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $result = $chat->setPhoto();
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => 1)));

            case 'get_chat_link':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));
                if ($chat->getAccessLevel() !== 9)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => $chat->getInviteLink())));

            case 'update_chat_link':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));
                if ($chat->getAccessLevel() !== 9)
                    die(json_encode(array('error' => 1)));

                if ($chat->updateInviteLink())
                    die(json_encode(array('response' => $chat->getInviteLink())));
                break;

            case 'toggle_write_access':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $user_id = intval(Request::get()->data['user_id']);

                $result = $chat->toggleWriteAccess($user_id);
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => $chat->findMemberById($user_id)->is_muted)));

            case 'add_user':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $user_ids = explode(',', Request::get()->data['user_ids']);
                $unique_ids = [];

                foreach ($user_ids as $index => $user_id)
                {
                    if ($index > 500) break;

                    if (!in_array(intval($user_id), $unique_ids))
                        $unique_ids[] = intval($user_id);
                }

                foreach ($unique_ids as $index => $user_id)
                {
                    if ($index > 100) break;

                    $result = $chat->addUser($user_id);
                    if ($result <= 0)
                    {
                        if ($result === -3) die(json_encode(array('error' => 3)));
                        if ($result === -1) die(json_encode(array('error' => 4)));

                        die(json_encode(array('error' => 1)));
                    }
                }

                die(json_encode(array('response' => 1)));

            case 'remove_user':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $user_id = intval(Request::get()->data['user_id']);

                $result = $chat->removeUser($user_id);
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => 1)));

            case 'update_chat_permissions':
                if ($chat->getType() !== 'conversation')
                    die(json_encode(array('error' => 1)));

                $group_name = strtolower(Request::get()->data['group_name']);
                $new_value  = intval(Request::get()->data['value']);

                $result = $chat->setPermissionsValue($group_name, $new_value);
                if ($result <= 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => 1)));

            case 'save_message':
                if ($chat->canWrite() !== 1)
                    die(json_encode(array('error' => 1)));

                $message = $chat->findMessageById(intval(Request::get()->data['message_id']));
                if ($message)
                {
                    $message->setText(strval(Request::get()->data['text']));
                    $message->setAttachments((new AttachmentsParser())->getObjects(Request::get()->data['attachments']));

                    if ($message->apply() === 1)
                    {
                        die(json_encode(array('id' => $message->getId())));
                    }
                }
                break;

            default:
                break;
        }
    } else
    {
        switch ($action) {
            case 'get_chats':
                die(json_encode(array_map(function (Chat $item) { return $item->toArray(); }, Chat::getList(intval(Request::get()->data['count']), intval(Request::get()->data['offset']), intval(Request::get()->data['only_chats']) ? 1 : 0))));

            case 'chat_create':
                $permissions = [
                    'can_change_title'  => 4,
                    'can_change_photo'  => 4,
                    'can_kick'          => 7,
                    'can_invite'        => 7,
                    'can_invite_bots'   => 8,
                    'can_mute'          => 5,
                    'can_pin_message'   => 4,
                    'delete_messages_2' => 7,
                    'can_change_levels' => 9,
                    'can_link_join'     => 0
                ];

                foreach ($permissions as $index => $value)
                {
                    if (isset(Request::get()->data['permission_' . $index]))
                    {
                        if (intval(Request::get()->data['permission_' . $index]) >= 0 && intval(Request::get()->data['permission_' . $index]) <= 9) $permissions[$index] = intval(Request::get()->data['permission_' . $index]);
                    }
                }

                $result = Conversation::create(strval(Request::get()->data['title']),
                    array_map(function ($user_id) {
                        return intval($user_id);
                    },
                        explode(',', Request::get()->data['members'])),
                    (new AttachmentsParser())->getObject(strval(Request::get()->data['photo'])),
                    $permissions
                );

                if ($result < 0)
                    die(json_encode(array('error' => 1)));

                die(json_encode(array('response' => $result * -1)));

            default:
                break;
        }
    }

    die(json_encode(array('error' => 1)));
}

/*

if (isset(Request::get()->data["action"]))
{
	$action = strtolower(Request::get()->data["action"]);

	switch ($action) {
		case 'get_chat_info_by_link':
			$chat = get_chat_by_query($connection, Request::get()->data['link_query'], (Context::get()->getCurrentUser() ? Context::get()->getCurrentUser()->getId() : 0));
			if (!$chat)
				die(json_encode(array('error'=>3)));

			$members = $chat->getMembers();
			if ($members['users']['user_'.(Context::get()->getCurrentUser() ? Context::get()->getCurrentUser()->getId() : 0)])
			{
				die(json_encode(array('error'=>2, 'chat_id'=>$members['users']['user_'.Context::get()->getCurrentUser()->getId()]['local_id'])));
			}

			die(json_encode(array(
				'title'         => $chat->title,
				'photo'         => $chat->photo,
				'members_count' => $members["count"]
			)));
		break;

		default:
		break;
	}

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action)
	{
		case "delete_messages":
			$chat_data = parse_id_from_string(Request::get()->data['s']);
			if (!$chat_data)
				die(json_encode(array('error' => 1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, Context::get()->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error' => 1)));

			$messages    = explode(',', strval(Request::get()->data["message_ids"]));
			$del_for_all = intval(Request::get()->data["delete_for_all"]);
			if ($uid < 0)
			{
				$chat = new Chat($connection, $uid);
				if (!$chat->isValid)
					die(json_encode(array('error' => 1)));

				$permissions = $chat->getPermissions();
				$members     = $chat->getMembers();

				$me = $members["users"]["user_".Context::get()->getCurrentUser()->getId()];
				if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
					die(json_encode(array('error' => 1)));
			}

			if (count($messages) > 500)
				die(json_encode(array('error' => 1)));

			$message_ids = [];
			foreach ($messages as $index => $id) {
				if (count($message_ids) >= 100)
					break;

				if (!in_array(intval($id), $message_ids))
					$message_ids[] = intval($id);
			}

			$result = delete_messages($connection, $uid, Context::get()->getCurrentUser()->getId(), $message_ids, $del_for_all, [
				'chat_id' => $sel,
				'is_bot'  => $is_bot
			], $permissions, $me);

			die('[]');
		break;

		case 'join_to_chat_by_link':
			$chat = get_chat_by_query($connection, Request::get()->data['link_query'], Context::get()->getCurrentUser()->getId());
			if (!$chat)
				die(json_encode(array('error'=>3)));

			$members = $chat->getMembers();
			if ($members['users']['user_'.Context::get()->getCurrentUser()->getId()])
			{
				die(json_encode(array('error'=>2, 'chat_id'=>$members['users']['user_'.Context::get()->getCurrentUser()->getId()]['local_id'])));
			}

			$owner_id_of_chat = 0;
			foreach ($members['users'] as $index => $user) {
				if ($user['flags']['level'] >= 9)
				{
					$owner_id_of_chat = intval($user['user_id']); break;
				}
			}

			$lid = $chat->addUser($owner_id_of_chat, Context::get()->getCurrentUser()->getId(), [
				'join_by_link' => true
			]);

			die(json_encode(array('response'=>$lid)));
		break;

		case 'set_typing_state':
			$chat_data = parse_id_from_string(Request::get()->data["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error' => 1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, Context::get()->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error' => 1)));

			if (can_write_to_chat($connection, $uid, Context::get()->getCurrentUser()->getId(), $chat_data))
			{
				$event = [
					'event'   => 'typing',
					'state'   => 'typing',
					'from_id' => Context::get()->getCurrentUser()->getId(),
					'peer_id' => 0
				];

				if ($uid > 0)
				{
					$user_ids = [$sel];
					$lids     = [Context::get()->getCurrentUser()->getId()];

					emit_event($user_ids, $lids, $event, Context::get()->getCurrentUser()->getId());
				}
				else
				{
					$chat_data = get_chat_info($connection, $uid, true, true, Context::get()->getCurrentUser()->getId());

					$user_ids = $chat_data["members"];
					$lids     = $chat_data["local_chat_ids"];

					emit_event($user_ids, $lids, $event, Context::get()->getCurrentUser()->getId());
				}

				die(json_encode(array('success'=>1)));
			}

			die(json_encode(array('error' => 1)));
		break;

		default:
		break;
	}
}*/

?>
