<?php
require __DIR__ . "/../../bin/objects/message.php";
require __DIR__ . '/../../bin/functions/messages.php';
require __DIR__ . '/../../bin/functions/users.php';
session_write_close();

if (isset($_POST["action"]))
{
	switch (strtolower($_POST["action"]))
	{
		case "send_message":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			require __DIR__ . '/../form/modules/send_message.php';
		break;
		case "save_message":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			require __DIR__ . '/../form/modules/save_message.php';
		break;
		case "chat_create":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$title = strval($_POST['title']);
			$users = explode(',', strval($_POST['members']));
			$photo = (new AttachmentsParser())->getObject(strval($_POST['photo']));

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

			foreach ($permissions as $index => $value) {
				if (isset($_POST['permission_' . $index]))
				{
					if (intval($_POST['permission_' . $index]) >= 0 && intval($_POST['permission_' . $index]) <= 9) $permissions[$index] = intval($_POST['permission_' . $index]);
				}
			}

			if (!function_exists('create_chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$result = create_chat($connection, $context->getCurrentUser()->getId(), $title, $users, $permissions, $photo);
			if ($result['error'] < 0 || $result['error'] === false)
			{
				return json_encode(array('error'=>intval($result)));
			}
				
			die(json_encode(array('response'=>intval($result))));
		break;
		case 'toggle_my_state':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!function_exists('parse_id_from_string'))
				require __DIR__ . '/../../bin/functions/messages.php';
			if (!class_exists('Chat'))
				require __DIR__ . '/../../bin/objects/chats.php';

			$chat_data = parse_id_from_string($_POST['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$members = $chat->getMembers(true);
			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me['flags']['is_kicked']) die(json_encode(array('error'=>1)));

			if ($me['flags']['is_leaved'])
			{
				$result = $chat->addUser($context->getCurrentUser()->getId(), $context->getCurrentUser()->getId(), $chat_data);
				if (!$result)
					die(json_encode(array('error'=>1)));

				die(json_encode(array('result'=>1)));
			} else
			{
				$result = $chat->removeUser($context->getCurrentUser()->getId(), $context->getCurrentUser()->getId(), $chat_data);
				
				if (!$result)
					die(json_encode(array('error'=>1)));

				die(json_encode(array('result'=>0)));
			}
		break;
		case "toggle_write_access":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!function_exists('parse_id_from_string'))
				require __DIR__ . '/../../bin/functions/messages.php';
			if (!class_exists('Chat'))
				require __DIR__ . '/../../bin/objects/chats.php';

			$chat_data = parse_id_from_string($_POST['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$members = $chat->getMembers(true);
			$permissions = $chat->getPermissions();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me['flags']['is_kicked'] || $me['flags']['is_leaved']) die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue("can_mute"))
				die(json_encode(array('error'=>1)));

			$mute_user = $members['users']['user_'.intval($_POST['user_id'])];
			if (!$mute_user || $mute_user['flags']['is_kicked']) 
				die(json_encode(array('error'=>1)));

			if ($chat->changeWriteAccess($context->getCurrentUser()->getId(), intval($_POST['user_id']), $mute_user['flags']['is_muted'], $chat_data))
			{
				die(json_encode(array('state'=>intval(!$mute_user['flags']['is_muted']))));
			};

			die(json_encode(array('error'=>1)));
		break;
		case "set_user_level":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!function_exists('parse_id_from_string'))
				require __DIR__ . '/../../bin/functions/messages.php';
			if (!class_exists('Chat'))
				require __DIR__ . '/../../bin/objects/chats.php';

			$chat_data = parse_id_from_string($_POST['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$members = $chat->getMembers(true);
			$permissions = $chat->getPermissions();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me['flags']['is_kicked'] || $me['flags']['is_leaved']) die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue("can_change_levels") || $me['flags']['level'] <= $work_user['flags']['level'])
				die(json_encode(array('error'=>1)));

			$work_user = $members['users']['user_'.intval($_POST['user_id'])];
			if (!$work_user || $work_user['flags']['is_kicked'])
				die(json_encode(array('error'=>1)));

			$new_level = intval($_POST['new_level']);
			if ($new_level >= $me['flags']['level'])
				die(json_encode(array('error'=>1)));

			if ($new_level < 0 || $new_level > 9)
				die(json_encode(array('error'=>1)));

			if ($chat->setUserLevel($work_user['user_id'], $new_level))
			{
				die(json_encode(array('response'=>1)));
			}

			die(json_encode(array('error'=>1)));
		break;
		case "add_user":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!function_exists('can_invite_to_chat'))
				require __DIR__ . '/../../bin/functions/users.php';
			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue("can_invite"))
				die(json_encode(array('error'=>2)));

			$users = explode(',', $_POST['user_ids']);
			$members_list = [];

			foreach ($users as $index => $user_id)
			{
				$user_id = intval($user_id);
				if ($index > 100) break;

				if (!can_invite_to_chat($connection, $context->getCurrentUser()->getId(), ($user_id > 0 ? (new User($user_id)) : new Bot($user_id * -1))))
					continue;

				$add_member = $members["users"]["user_".$user_id];
				if (!$add_member['is_kicked'] && $add_member['is_leaved'])
					die(json_encode(array('error'=>3)));

				if (!$add_member['is_kicked'] && !$add_member['is_leaved'] && $add_member)
					die(json_encode(array('error'=>4)));

				if (!in_array($user_id, $members_list) && $user_id !== $context->getCurrentUser()->getId())
				{
					$members_list[] = $user_id;
				}

				$chat->addUser($context->getCurrentUser()->getId(), $user_id, $chat_data);
			}

			die(json_encode(array('response'=>1)));
		break;
		case "remove_user":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$kick_id = intval($_POST['user_id']);

			if (!$chat->removeUser($context->getCurrentUser()->getId(), $kick_id, $chat_data))
			{
				die(json_encode(array('error'=>1)));
			};

			die(json_encode(array('response'=>1)));
		break;
		case "delete_messages":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat_data = parse_id_from_string($_REQUEST['s']);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$messages    = explode(',', strval($_POST["message_ids"]));
			$del_for_all = intval($_POST["delete_for_all"]);
			if ($uid < 0)
			{
				$chat = new Chat($connection, $uid);
				if (!$chat->isValid)
					die(json_encode(array('error'=>1)));

				$permissions = $chat->getPermissions();
				$members     = $chat->getMembers();

				$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
				if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
					die(json_encode(array('error'=>1)));
			}

			if (count($messages) > 500)
				die(json_encode(array('error'=>1)));

			$message_ids = [];
			foreach ($messages as $index => $id) {
				if (count($message_ids) >= 100)
					break;

				if (!in_array(intval($id), $message_ids))
					$message_ids[] = intval($id);
			}

			$result = delete_messages($connection, $uid, $context->getCurrentUser()->getId(), $message_ids, $del_for_all, [
				'chat_id' => $sel,
				'is_bot'  => $is_bot
			], $permissions, $me);
			
			die('[]');
		break;
		case "read_chat":
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat_data = parse_id_from_string($_POST['peer_id']);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));
			
			die(json_encode(array('success'=>intval(read_chat($connection, $uid, $context->getCurrentUser()->getId(), [
				'chat_id' => $sel,
				'is_bot'  => $is_bot
			])))));
		break;
		case 'get_members':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);

			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			$response = [];

			foreach ($members['users'] as $index => $user) {
				if ($user['flags']['is_leaved'] || $user['flags']['is_kicked']) continue;

				$user_id = intval($user['user_id']);

				$entity = intval($user_id) > 0 ? new User(intval($user_id)) : new Bot(intval($user_id)*-1);

				$object = [];
				if (!$entity->valid()) 
				{
					$object = [
						'is_deleted' => 1
					];

					if ($user_id > 0)
						$object['user_id'] = $user_id;
					else
						$object['bot_id'] = $user_id * -1;
				} else
					$object = $entity->toArray();

				$object['chat_info'] = [
					'access_level' => intval($user['flags']['level']),
					'invited_by'   => intval($user['invited_by'])
				];

				if ($user['flags']['is_muted']) $object['chat_info']['is_muted'] = true;

				$response[] = $object;
			}

			die(json_encode($response));
		break;
		case 'get_messages':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('list'=>[])));

			$offset = (intval($_POST['page'])*100)-100;

			$messages_list = get_chat_messages($connection, $uid, $context->getCurrentUser()->getId(), $offset, 100, $chat_data);
			if (!$messages_list)
				die(json_encode(array('list'=>[])));

			$pinned_messages = [];
			if ($uid < 0)
			{
				if (!class_exists('Chat'))
					require __DIR__ . "/../../bin/objects/chats.php";

				$chat = new Chat($connection, $uid);
				if ($chat->isValid)
				{
					$permissions = $chat->getPermissions();
					$members     = $chat->getMembers();

					$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
					if (!(!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"]))
					{
						$pinned_messages = get_pinned_messages($connection, $uid);
					}
				}
			}

			die(json_encode(array('list'=>$messages_list, 'pinned'=>$pinned_messages)));
		break;
		case 'toggle_notifications':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());

			if (!$uid)
				die(json_encode(array('error'=>1)));

			$new_value = intval(boolval(intval($_POST['new_value'])));

			die(json_encode(array(
				'success' => $connection->prepare("UPDATE messages.members_chat_list SET notifications = ? WHERE uid = ? AND user_id = ? LIMIT 1;")->execute([intval($new_value), intval($uid), intval($context->getCurrentUser()->getId())])
			)));
		break;
		case 'toggle_pinned_messages':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());

			if (!$uid)
				die(json_encode(array('error'=>1)));

			$new_value = intval(boolval(intval($_POST['new_value'])));

			die(json_encode(array(
				'success' => $connection->prepare("UPDATE messages.members_chat_list SET show_pinned_messages = ? WHERE uid = ? AND user_id = ? LIMIT 1;")->execute([intval($new_value), intval($uid), intval($context->getCurrentUser()->getId())])
			)));
		break;
		case 'get_chat_link':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < 9)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$chat->getLink())));
		break;
		case 'update_chat_link':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < 9)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$chat->updateLink())));
		break;
		case 'update_chat_permissions':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < 9)
				die(json_encode(array('error'=>1)));

			$group_name = strtolower($_POST['group_name']);
			$new_value  = intval($_POST['value']);

			if ($new_value > 9 || $new_value < 0)
				die(json_encode(array('error'=>1)));

			$result = $permissions->setValue($group_name, $new_value);
			if (!$result)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>1)));
		break;
		case 'get_chat_info_by_link':
			if ($context->getCurrentUser() && $context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!function_exists('get_chat_by_query'))
				require __DIR__ . '/../../bin/functions/chats.php';
			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat = get_chat_by_query($connection, $_POST['link_query'], ($context->getCurrentUser() ? $context->getCurrentUser()->getId() : 0));
			if (!$chat)
				die(json_encode(array('error'=>3)));

			$members = $chat->getMembers();
			if ($members['users']['user_'.($context->getCurrentUser() ? $context->getCurrentUser()->getId() : 0)])
			{
				die(json_encode(array('error'=>2, 'chat_id'=>$members['users']['user_'.$context->getCurrentUser()->getId()]['local_id'])));
			}

			die(json_encode(array(
				'title'         => $chat->title,
				'photo'         => $chat->photo,
				'members_count' => $members["count"]
			)));
		break;
		case 'join_to_chat_by_link':
			if (!function_exists('get_chat_by_query'))
				require __DIR__ . '/../../bin/functions/chats.php';
			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$chat = get_chat_by_query($connection, $_POST['link_query'], $context->getCurrentUser()->getId());
			if (!$chat)
				die(json_encode(array('error'=>3)));

			$members = $chat->getMembers();
			if ($members['users']['user_'.$context->getCurrentUser()->getId()])
			{
				die(json_encode(array('error'=>2, 'chat_id'=>$members['users']['user_'.$context->getCurrentUser()->getId()]['local_id'])));
			}

			$owner_id_of_chat = 0;
			foreach ($members['users'] as $index => $user) {
				if ($user['flags']['level'] >= 9)
				{
					$owner_id_of_chat = intval($user['user_id']); break;
				}
			}

			$lid = $chat->addUser($owner_id_of_chat, $context->getCurrentUser()->getId(), [
				'join_by_link' => true
			]);

			die(json_encode(array('response'=>$lid)));
		break;
		case 'update_chat_photo':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";
			if (!class_exists('AttachmentsParser'))
				require __DIR__ . "/../../bin/objects/attachment.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue('can_change_photo'))
				die(json_encode(array('error'=>1)));

			$photo = (new AttachmentsParser())->getObject($_POST['photo']);
			if (!$photo)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>$chat->updatePhoto($photo, $context->getCurrentUser()->getId(), [
				'chat_id'   => $chat_data['chat_id'],
				'is_bot'    => false,
				'new_src'   => $photo->getLink(),
				'new_query' => $photo->getQuery()
			]))));
		break;
		case 'delete_chat_photo':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue('can_change_photo'))
				die(json_encode(array('error'=>1)));

			die(json_encode(array('success'=>$chat->updatePhoto(null, $context->getCurrentUser()->getId(), [
				'chat_id'   => $chat_data['chat_id'],
				'is_bot'    => false
			]))));
		break;
		case 'set_chat_title':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";
			if (!class_exists('AttachmentsParser'))
				require __DIR__ . "/../../bin/objects/attachment.php";

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			$chat = new Chat($connection, $uid);
			if (!$chat->isValid)
				die(json_encode(array('error'=>1)));

			$permissions = $chat->getPermissions();
			$members     = $chat->getMembers();

			$me = $members["users"]["user_".$context->getCurrentUser()->getId()];
			if (!$me || $me["flags"]["is_leaved"] || $me["flags"]["is_kicked"])
				die(json_encode(array('error'=>1)));

			if ($me['flags']['level'] < $permissions->getValue('can_change_photo'))
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$chat->setTitle($context->getCurrentUser()->getId(), strval($_POST['new_title']), [
				'chat_id'   => $chat_data['chat_id'],
				'is_bot'    => false,
				'new_title' => strval($_POST['new_title'])
			]))));
		break;
		case 'set_typing_state':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			if (!class_exists('Chat'))
				require __DIR__ . "/../../bin/objects/chats.php";
			if (!function_exists('emit_event'))
				require __DIR__ . '/../../bin/emitters.php';

			$chat_data = parse_id_from_string($_POST["peer_id"]);
			if (!$chat_data)
				die(json_encode(array('error'=>1)));

			$sel    = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);
			$uid    = get_uid_by_lid($connection, $sel, $is_bot, $context->getCurrentUser()->getId());
			if (!$uid)
				die(json_encode(array('error'=>1)));

			if (can_write_to_chat($connection, $uid, $context->getCurrentUser()->getId(), $chat_data))
			{
				$event = [
					'event'   => 'typing',
					'state'   => 'typing',
					'from_id' => $context->getCurrentUser()->getId(),
					'peer_id' => 0
				];

				if ($uid > 0)
				{
					$user_ids = [$sel];
					$lids     = [$context->getCurrentUser()->getId()];

					emit_event($user_ids, $lids, $event, $context->getCurrentUser()->getId());
				}
				else
				{
					$chat_data = get_chat_info($connection, $uid, true, true, $context->getCurrentUser()->getId());

					$user_ids = $chat_data["members"];
					$lids     = $chat_data["local_chat_ids"];

					emit_event($user_ids, $lids, $event, $context->getCurrentUser()->getId());
				}

				die(json_encode(array('success'=>1)));
			}

			die(json_encode(array('error'=>1)));
		break;
		case 'get_chats':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			die(json_encode(get_chats($connection, $context->getCurrentUser()->getId(), intval($_POST['offset']), intval($_POST['count']), intval($_POST['only_chats']))));
		break;
		case 'clear':
			if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));
			if ($context->getCurrentUser()->isBanned()) die(json_encode(array('error'=>1)));

			$id  = parse_id_from_string(strtolower($_POST["chat_id"]));
			$uid = get_uid_by_lid($connection, $id["chat_id"], $id["is_bot"], $context->getCurrentUser()->getId());

			$result = clear_chat($connection, $uid, $context->getCurrentUser()->getId(), [
				'chat_id' => $id["chat_id"],
				'is_bot'  => $id["is_bot"]
			]);
			die('[]');
		break;
		default:
		break;
	}
}

/*
 * messages file...
*/
if (isset($_REQUEST["s"]) && !is_empty($_REQUEST["s"]))
{
	if (strtolower($_REQUEST["a"]) === "e")
	{
		require __DIR__ . '/../form/modules/save_message.php';
	}
}

// getting chat list and parse it to html if not empty "s" param
if (!isset($_REQUEST["s"]) || is_empty($_REQUEST["s"]))
{
	require __DIR__ . '/../form/modules/messages_list_html.php';
}

?>