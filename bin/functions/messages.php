<?php
/**
 *
 * Contains a messages functions (send, get, etc)
 *
*/

require_once __DIR__ . '/../platform-tools/emitters.php';
require_once __DIR__ . '/../objects/poll.php';
require_once __DIR__ . '/theming.php';
require_once __DIR__ . "/users.php";

/**
 * Parsing chat credentials to workable data.
 * 
 * @return array with local_id and boolean shows bot dialog or not
*/
function parse_id_from_string ($sel)
{
	// default result.
	$result = intval($sel);
	$is_bot = false;

	// if it is not integer - it may be a bot chat.
	if ($result === 0)
	{
		$result = intval(explode('b', $sel)[1]);
		if ($result > 0) $is_bot = true;
	}

	// if it is already 0 - it is incorrect string!
	if ($result === 0) return false;

	// parsed data.
	return ['chat_id' => $result, 'is_bot'  => $is_bot];
}

/**
 * Clear the chat.
 *
 * @return true if chat cleared, or false if error.
 * @throws Event. It send event for user.
 *
 * Parameters:
 * @param $uid - uid of the chat.
 * @param $user_id - user_id which cleares the chat.
 * @param $additional - array who must contain result of parse_id_from_string() function call.
*/
function clear_chat ($connection, $uid, $user_id, $additional = [])
{
	// DEFAULT PARAMS
	$leaved_time  = 0;
	$return_time  = 0;
	$is_leaved    = 0;
	$is_kicked    = 0;
	$last_message = 0;

	$res = $connection->prepare("SELECT is_leaved, is_kicked, return_time, leaved_time FROM messages.members_chat_list WHERE uid = ? AND user_id = ?;");
	$res->execute([$uid, $user_id]);

	// selecting data of this chat.
	$result = $res->fetch(PDO::FETCH_ASSOC);

	// chat not exists!!!
	if (!$result)
		return false;

	// setting new params
	$is_leaved   = intval($result["is_leaved"]);
	$is_kicked   = intval($result["is_kicked"]);
	$return_time = intval($result["return_time"]);
	$leaved_time = intval($result["leaved_time"]);

	// setting up last_message_id;
	$res = $connection->prepare(get_chat_query($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, $user_id, true, 0)); $res->execute([$user_id]);

	// read the chat and send event.
	read_chat($connection, $uid, $user_id, $additional);
	$event = [
		'event' => 'cleared_chat'
	];
	$additional["is_bot"] ? $event["bot_peer_id"] = intval($additional["chat_id"]) : $event["peer_id"] = intval($additional["chat_id"]);
	emit_event([$user_id], [0], $event);

	// resolved id. Clearing.
	$connection->prepare("UPDATE messages.members_chat_list SET cleared_message_id = ? WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($res->fetch(PDO::FETCH_ASSOC)["local_chat_id"]), intval($user_id), intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET hidden = 1 WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($user_id), intval($uid)]);

	// cleared.
	return true;
}

/**
 * Receives uid of the chat by lid.
 * @return uid of the chat.
 *
 * Parameters:
 * @param $lid - local_chat_id of chat.
 * @param $is_bot - boolean of bot chat.
 * @param $user_id - user_id which $lid bound.
 * 
 * $lid and $is_bot may be received from parse_id_from_string() function call.
*/
function get_uid_by_lid ($connection, $lid, $is_bot = false, $user_id)
{
	// if is bot and lid > 0 - it is chat with bot. ID must be negative.
	if ($is_bot && $lid > 0) $lid = $lid*-1;

	// selecting uid.
	$res = $is_bot ? $connection->prepare("SELECT uid FROM messages.members_chat_list WHERE lid = ".$lid." AND uid > 0 AND user_id = ".$user_id." LIMIT 1;") : $connection->prepare("SELECT uid FROM messages.members_chat_list WHERE lid = ".$lid." AND user_id = ".$user_id." LIMIT 1;");
	$res->execute();

	// fetching uid.
	$uid = $res->fetch(PDO::FETCH_ASSOC)["uid"];

	// if uid not found - chat does not bound on the $user_id account.
	if (!$uid)
		return false;

	// return uid.
	return intval($uid);
}

/**
 * Receives a chat info by uid
 * @return array with chat title, photo and members with lids.
 *
 * Parameters:
 * @param $uid - uid of the chat.
*/
function get_chat_info ($connection, $uid, $without_bots = false, $without_me = false, $my_id = 0)
{
	// this function works only with multi-chats.
	if ($uid > 0) return false;

	// getting title and photo.
	$res = $connection->prepare("SELECT title, photo FROM messages.members_engine_1 WHERE uid = ? LIMIT 1;");
	$res->execute([strval($uid)]);

	// chat info array.
	$chat_info = $res->fetch(PDO::FETCH_ASSOC);

	// if not chats exists
	if (!$chat_info) return false;

	// getting all member ids and local ids.
	$res = $connection->prepare("SELECT DISTINCT user_id, lid, permissions_level FROM messages.members_chat_list WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 ".($without_bots ? "AND user_id > 0 ": "")."ORDER BY permissions_level DESC;");
	$res->execute([strval($uid)]);
	$users = $res->fetchAll(PDO::FETCH_ASSOC);
	
	// members list
	$done  = [];

	// local ids list.
	$lids = [];

	foreach ($users as $index => $item) {
		if ($without_me && (intval($item['user_id']) === intval($my_id))) continue;

		$done[] = intval($item["user_id"]);
		$lids[] = intval($item["lid"]);
	}

	$count = count($done);

	// generating url of chat_photo
	$src = Project::ATTACHMENTS_URL . '/' . $chat_info["photo"];
	if (!$chat_info["photo"] || $chat_info["photo"] === "")
		$src = Project::DEVELOPERS_URL . '/images/default.png';

	// return result.
	return [
		'title'          => $chat_info["title"],
		'photo'          => $src,
		'count'          => $count,
		'members'        => $done,
		'local_chat_ids' => $lids
	];
}

/**
 * Gets chat by uid for user
 * @return array with chat data
 *
 * Parameters:
 * @param $uid - uid of chat
 * @param $user_id - user_id for who gets
*/
function get_chat_data_by_uid ($connection, $uid, $user_id, $chat_data = [])
{
	// requesting chat params by uid.
	$res = $connection->prepare("SELECT DISTINCT uid, is_read, notifications, show_pinned_messages, last_read_message_id, lid, leaved_time, return_time, is_leaved, is_kicked, is_muted, cleared_message_id, last_time FROM messages.members_chat_list WHERE uid = ? AND user_id = ? AND lid != 0 ORDER BY last_time DESC LIMIT 1;");

	if ($res->execute([intval($uid), intval($user_id)]))
	{
		// chat info
		$chat = $res->fetch(PDO::FETCH_ASSOC);
		if ($chat)
		{
			// all data of this chat
			$uid         = intval($chat["uid"]);
			$lid         = intval($chat["lid"]);
			$leaved_time = intval($chat["leaved_time"]);
			$return_time = intval($chat["return_time"]);
			$is_leaved   = intval($chat["is_leaved"]);
			$is_kicked   = intval($chat["is_kicked"]);
			$is_muted    = intval($chat["is_muted"]);
			$cl_msg_id   = intval($chat["cleared_message_id"]);
			$last_unread = intval($chat["last_read_message_id"]);
			$is_read     = boolval(intval($chat['is_read']));
			$show_notes  = boolval(intval($chat['notifications']));
			$pinned_msg  = boolval(intval($chat['show_pinned_messages']));;

			$res = $connection->prepare(get_chat_query($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, $user_id, true, $cl_msg_id));
			if ($res->execute([intval($user_id)]))
			{
				// chat's last message
				$dialogs       = $res->fetch(PDO::FETCH_ASSOC);
				$local_chat_id = intval($dialogs["local_chat_id"]);

				$unread_count  = intval($local_chat_id - $last_unread);

				// chat array
				$chat = [
					'peer_id' => intval($lid)
				];

				// setting chat metadata
				$chat['metadata'] = [];
				$chat['metadata']['is_read_by_me'] = $is_read;
				$chat['metadata']['unread_count']  = $unread_count = $unread_count <= 0 ? 0 : intval($unread_count);
				$chat['metadata']['notifications'] = $show_notes;

				if ($uid < 0)
					$chat['metadata']['show_pinned_messages'] = $pinned_msg;

				if (intval($user_id) > 0 && $local_chat_id)
				{
					$chat['last_message'] = message_to_array($connection, get_message_array_by_credentials($connection, $uid, $local_chat_id), [
						'chat_id' => $lid,
						'is_bot'  => $lid < 0 && $uid > 0 ? true : false
					]);

					unset($chat['last_message']['keyboard']);
				}

				$chat['chat_info'] = [];
				if ($lid > 0 && $uid > 0)
				{
					$chat['chat_info']['is_multi_chat'] = false;
					$chat['chat_info']['data']          = [];

					$user = new User($lid);
					if ($user->valid())
						$chat['chat_info']['data'] = $user->toArray('*');
				}
				if ($lid < 0 && $uid > 0)
				{
					$chat['chat_info']['is_multi_chat'] = false;
					$chat['chat_info']['is_bot_chat']   = true;
					$chat['chat_info']['data']          = [];

					$bot = new Bot($lid*-1);
					if ($bot->valid())
						$chat['chat_info']['data'] = $bot->toArray('*');

					unset($chat['peer_id']);
					$chat['bot_peer_id'] = intval($lid);
				}
				if ($lid < 0 && $uid < 0)
				{
					$chat['chat_info']['is_multi_chat'] = true;

					if ($is_kicked || $is_leaved || $is_muted) $chat['metadata']['permissions'] = [];
					if (!$is_kicked && $is_leaved) $chat['metadata']['permissions']['is_leaved'] = boolval($is_leaved);
					if ($is_kicked) $chat['metadata']['permissions']['is_kicked'] = boolval($is_kicked);
					if ($is_muted) $chat['metadata']['permissions']['is_muted'] = boolval($is_muted);

					$chat_info = get_chat_info($connection, $uid);
					if ($chat_info)
					{
						$chat["chat_info"]["data"] = [
							"title"         => $chat_info['title'],
							"photo_url"     => $chat_info['photo']
						];

						if (!$is_kicked && !$is_leaved) $chat["chat_info"]["data"]["members_count"] = intval($chat_info["count"]);
					}
				}

				return $chat;
			}
		} else {
			// chat array
			$chat = [
				'peer_id' => intval($chat_data["chat_id"])
			];

			$chat['chat_info'] = [];
			if (!$chat_data["is_bot"] && $chat_data["chat_id"] > 0)
			{
				$user = new User($chat_data["chat_id"]);
				if (!$user->valid()) return false;

				$chat['chat_info']['is_multi_chat'] = false;
				$chat['chat_info']['data']          = $user->toArray('*');
			}

			// setting chat metadata
			$chat['metadata'] = [];
			$chat['metadata']['not_created_chat'] = true;

			if ($chat_data["is_bot"] && $chat_data["chat_id"] > 0)
			{
				$bot = new Bot($chat_data["chat_id"]);
				if (!$bot->isAlive) return false;

				$chat['chat_info']['is_multi_chat'] = false;
				$chat['chat_info']['is_bot_chat']   = true;
				$chat['chat_info']['data']          = $bot->toArray('*');

				unset($chat['peer_id']);
				$chat['bot_peer_id'] = intval($chat_data["chat_id"]*-1);
			}

			if (!$chat_data["is_bot"] && $chat_data["chat_id"] < 0)
			{
				return false;
			}

			return $chat;
		}
	}

	return false;
}

/**
 * Gets 50 first chats of selected $user_id
 * who is not clear and have a previewable message.
 * @return array with chats.
 *
 * Parameters:
 * @param $user_id - user id who chat will get.
*/
function get_chats ($connection, $user_id, $offset = 0, $count = 30, $only_chats = false)
{
	// max 100 chats per call
	if (intval($count) < 1 || intval($count) > 100) return [];

	// requesting all chats list.
	$res = $connection->prepare("SELECT DISTINCT uid, last_time FROM messages.members_chat_list WHERE hidden = 0 AND user_id = ? AND lid != 0".($only_chats ? ' AND uid < 0' : '')." ORDER BY last_time DESC LIMIT ".intval($offset).",".intval($count).";");

	$res->execute([intval($user_id)]);
	$chats = $res->fetchAll(PDO::FETCH_ASSOC);

	// selecting unique chat uids.
	$uids = [];
	foreach ($chats as $key => $value) 
	{
		if (!in_array(intval($value['uid']), $uids))
		{
			$uids[] = intval($value['uid']);
		}
	}

	// parsing it and get chats data
	$result = [];
	foreach ($uids as $index => $uid) 
	{
		$chat = get_chat_data_by_uid($connection, $uid, $user_id);

		if (!$chat) continue;
		if (!$chat["last_message"]) continue;

		$result[] = $chat;
	}

	return $result;
}

/**
 * Gets messages list (100) of selected $uid
 * @return array with messages
 *
 * Parameters
 * @param $uid - uid of the chat
 * @param $user_id - userid who get the chat
 * @param start_offset, end_offset - offsets
 * @param $sel - array - result of parse_id_from_string() function call
*/
function get_chat_messages ($connection, $uid, $user_id, $offset = 0, $count = 100, $sel = [])
{
	// DEFAULT PARAMS
	$leaved_time        = 0;
	$return_time        = 0;
	$is_leaved          = 0;
	$is_kicked          = 0;

	// checking chat info for selected user.
	$res = $connection->prepare("SELECT is_leaved, is_kicked, return_time, leaved_time, cleared_message_id, keyboard_onetime, keyboard_created FROM messages.members_chat_list WHERE uid = ? AND user_id = ? LIMIT 1;");
	$res->execute([strval($uid), strval($user_id)]);
	$result = $res->fetch(PDO::FETCH_ASSOC);

	// if not chat exists.
	if (!$result) return false;

	// setting new params.
	$is_leaved          = intval($result["is_leaved"]);
	$is_kicked          = intval($result["is_kicked"]);
	$return_time        = intval($result["return_time"]);
	$leaved_time        = intval($result["leaved_time"]);
	$cleared_message_id = intval($result["cleared_message_id"]);

	// getting chat query
	$res = $connection->prepare(get_chat_query($uid, $leaved_time, $return_time, $is_kicked, $is_leaved, $user_id, false, $cleared_message_id, $offset, $count));
	$res->execute([strval($user_id)]);

	// keyboard params
	$keyboard_created = boolval(intval($result["keyboard_created"]));
	if ($keyboard_created)
	{
		$keyboard_onetime = intval($result["keyboard_onetime"]);
		$keyboard_loaded  = false;
		$keyboard         = false;
		$last_keyboard_id = 0;

		if ($keyboard_onetime > 1)
		{
			$res_k = $connection->prepare("SELECT keyboard FROM messages.chat_engine_1 WHERE uid = ? AND local_chat_id = ? LIMIT 1;");
			$res_k->execute([$uid, $keyboard_onetime]);
			$data = $res_k->fetch(PDO::FETCH_ASSOC)["keyboard"];
			if ($data && $data !== "hide")
			{
				$keyboard = $data;
				$keyboard_loaded = true;
			}
		}
	}

	// fetching messages
	$res      = $res->fetchAll(PDO::FETCH_ASSOC);
	$res_done = [];
	foreach ($res as $index => $message)
	{
		// convert message to standartized array
		$msg = message_to_array($connection, $message, $sel);
		if ($msg)
		{
			if ($keyboard_onetime === 1 && $keyboard_created && !$keyboard_loaded && $msg['keyboard'])
			{
				if ($msg["id"] > $last_keyboard_id)
				{
					if ($msg['keyboard'] !== '"hide"' && $msg['keyboard'] !== 'hide')
					{
						$keyboard = $msg['keyboard'];
						$last_keyboard_id = $msg["id"];
					}
				}
			}
			unset($msg['keyboard']);

			$res_done[] = $msg;
		}
	}
	if ($keyboard === "hide") $keyboard = [];

	// load onetime!
	if ($keyboard_created && $keyboard && $keyboard_onetime === 1) $keyboard_loaded = true;

	// add keyboard
	if ($keyboard_created && $keyboard && $keyboard_loaded) $res_done[0]['keyboard'] = json_decode($keyboard);

	// return messages.
	read_chat($connection, $uid, $user_id, $sel);
	return array_reverse($res_done);
}

/**
 * Gets pinned messages from the chat
 * @return array with message objects
 *
 * Parameters:
 * @param $uid - global id of chat
*/
function get_pinned_messages ($connection, $uid)
{
	$res = $connection->prepare("SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard FROM messages.members_engine_1 WHERE uid = ? AND deleted_for_all != 1 ORDER BY local_chat_id LIMIT 100;");

	if ($res->execute([intval($uid)]))
	{
		$data = $res->fetchAll(PDO::FETCH_ASSOC);

		$res_done = [];
		foreach ($data as $index => $message)
		{
			// convert message to standartized array
			$msg = message_to_array($connection, $message, $sel);
			if ($msg)
			{
				unset($msg['keyboard']);

				$res_done[] = $msg;
			}
		}

		return $res_done;
	}

	return [];
}

/**
 * get message data by local_chat_id in selected uid.
 * @return array with message data.
 *
 * Parameters:
 * @param $uid - uid of chat who contains needed message
 * @param $local_chat_id - message id.
*/
function get_message_array_by_credentials ($connection, $uid, $local_chat_id, $fwd = false)
{
	$res = $connection->prepare("SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments FROM messages.chat_engine_1 WHERE ".($fwd ? "" : "deleted_for_all != 1 AND ")."uid = ? AND local_chat_id = ? ORDER BY local_chat_id DESC LIMIT 1;");
	$res->execute([strval($uid), strval($local_chat_id)]);

	return $res->fetch(PDO::FETCH_ASSOC);
}

/**
 * Standartize the message data.
 * @return array of message.
 *
 * Parameters:
 * @param $message - message data
 * @param $sel - result of parse_id_from_string() function call;
 * @param $counter - depth for FWD parsing.
*/
function message_to_array ($connection, $message, $sel = [], $counter = 0)
{
	// max depth - 40.
	if ($counter > 40) return false;

	// standard message object
	$done_message = [
		'id'          => intval($message["local_chat_id"]),
		'time'        => intval($message["time"]),
		'text'        => htmlspecialchars_decode($message["text"]),
		'from_id'     => intval($message["owner_id"]),
		'fwd'         => [],
		'attachments' => [],
		'keyboard'    => $message['keyboard'] === '"hide"' ? NULL : $message['keyboard']
	];

	if (intval($message['is_edited']))
	{	
		$done_message['is_edited'] = true;
	}

	// if message has FWD messages - parse it.
	if (!is_empty($message["attachments"]))
	{
		$credentials    = explode("_", $message["attachments"]);
		$uid            = intval($credentials[1]);
		$local_chat_ids = explode(",", $credentials[0]);

		foreach ($local_chat_ids as $index => $id)
		{
			$message_array = get_message_array_by_credentials($connection, $uid, intval($id), true);
			if ($message_array)
			{
				$message_array = message_to_array($connection, $message_array, $sel, $counter++);
				if ($message_array)
					$done_message["fwd"][] = $message_array;
			}
		}
	}

	// if message has attachments - parse it.
	if (!is_empty($message["reply"]))
	{
		$attachments = (new AttachmentsParser())->getObjects($message["reply"]);
		foreach ($attachments as $index => $attachment)
		{
			$done_message["attachments"][] = $attachment->toArray();
		}
	}

	// if it is a service message - create event
	if ($message["event"])
	{
		$event = [
			"action" => strval($message["event"]),
		];

		if (intval($message["to_id"]) !== 0)
			$event["to_id"] = intval($message["to_id"]);

		if ($message["new_title"] && $message["new_title"] !== "")
			$event["new_title"] = $message["new_title"];

		if ($message["new_src"] && $message["new_src"] !== "")
			$event["new_photo_url"] = DEFAULT_ATTACHMENTS_URL.'/'.$message["new_src"];

		$done_message["event"] = $event;
	}

	// setting p a local user data
	if ($sel["chat_id"])
	{
		// if message from dialog with bot
		if ($sel["is_bot"])
			$done_message["bot_peer_id"] = intval($sel["chat_id"]);
		else
			$done_message["peer_id"] = intval($sel["chat_id"]);
	}

	if ($counter > 0)
		unset($done_message['keyboard']);

	// return message.
	return $done_message;
}

/**
 * Checks allowance to write to this chat or user's chat.
 *
 * Parameters:
 * @param:uid - uid of checking chat
 * @param:user_id - user id checking permission shiwch
 * @param:chat_data (not required) - chat data if uid > 0
 *
 * @return false is user cant write, true - if can
 * @return 2 if user can write but cant get messages (leaved chat)
 * @return 1 if user can get new messages but cant wrtie (muted)
*/
function can_write_to_chat ($connection, $uid, $user_id, $chat_data = [])
{
	// non-existing chat.
	if ($user_id === 0) return false;

	// if it is a not multi-chat or chat not exists and not multi-chat
	if ($uid > 0 || !$uid)
	{
		// if bot writes to user firstly
		if ($user_id < 0)
		{
			$res = $connection->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1;");
			$res->execute([$chat_data["chat_id"], $user_id*-1]);
			$info = $res->fetch(PDO::FETCH_ASSOC);

			// if user restricted messsages from bot or user not written to bot.
			if (intval($info["state"]) === -1 || !$info["state"] || !$info) return false;

			// also ok.
			return true;
		}
		else
		{	
			// if user writes to user or bot.
			$to_id  = intval($chat_data["chat_id"]);
			$is_bot = boolval($chat_data["is_bot"]);

			// if wrtiting to bot
			if ($is_bot)
			{
				// checking bot prvacy settings
				$res = $connection->prepare("SELECT owner_id, settings FROM bots.info WHERE id = ? LIMIT 1;");
				$res->execute([$to_id * -1]);
				$info = $res->fetch(PDO::FETCH_ASSOC);

				if (!$info)
				{
					$res = $connection->prepare("SELECT owner_id, settings FROM bots.info WHERE id = ? LIMIT 1;");
					$res->execute([$to_id]);
					$info = $res->fetch(PDO::FETCH_ASSOC);
				}

				// bot not found
				if (!$info) return false;

				$owner_id  = intval($info["owner_id"]);
				$can_write = intval(json_decode($info["settings"], true)["privacy"]["can_write_messages"]);

				// if bot disabled messages and user is not owner - false;
				if ($can_write === 2 && ($owner_id !== $user_id)) return false;

				// OK!
				return true;
			}
			else
			{
				// if user writes to itself - it always can do it.
				if ($to_id === $user_id) return true;

				// if user blacklisted - can not write.
				if (in_blacklist($connection, $to_id, $user_id)) return false;

				// checking privacy settings.
				$res = $connection->prepare("SELECT id, settings_privacy_can_write_messages, is_banned FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1;");
				$res->execute([$to_id]);
				$info = $res->fetch(PDO::FETCH_ASSOC);

				// can now write to banned users
				if (intval($info['is_banned'])) return false;

				// if not settings has - not user exists.
				if (!$info) return false;

				// privacy state
				$can_write = intval($info['settings_privacy_can_write_messages']);
				
				// 2 - nobody can write to user
				if ($can_write === 2) return false;

				// 0 - all can write to user
				if ($can_write === 0 || $can_write === NULL) return true;

				// 1 - only friends can write to user.
				if ($can_write === 1 && is_friends($connection, $to_id, $user_id)) return true;

				return false;
			}
		}
	}
	else
	{
		// if it is a multi-chat
		$res = $connection->prepare("SELECT is_kicked, is_leaved, is_muted FROM messages.members_chat_list WHERE uid = ? AND user_id = ? LIMIT 1;");
		$res->execute([$uid, $user_id]);

		// if chat is ok.
		$info = $res->fetch(PDO::FETCH_ASSOC);

		// if not chats exists
		if (!$info) return false;

		// if user kicked
		if (intval($info["is_kicked"]) === 1) return false;

		// if user leaved
		if (intval($info["is_leaved"]) === 1) return 2;

		// if user muted
		if (intval($info["is_muted"]) === 1) return 1;

		// OK
		return true;
	}

	// other errors.
	return false;
}

/**
 * Gets last uid of all yunnet dialogs.
 * @return last free uid.
 *
 * Parameters:
 * @param $dialog - true if uid for dialog, false if uid needed for multi-chat
*/
function get_last_uid ($dialog = true)
{
	$params = [
		'operation' => 'get_uid',
		'to_dialog' => $dialog
	];

	// connecting to text-engine;
	$socket  = socket_create(AF_UNIX, SOCK_STREAM, 0);
	$connect = socket_connect($socket, __DIR__ . "/../managers/sockets/text_engine.sock");
	
	// if not text-engine online
	if (!$connect)
		return false;

	// fetching result.
	$result = socket_write($socket, json_encode($params));
	if (!$result)
		return false;

	$last_uid = intval(socket_read($socket, 50));
	socket_close($socket);

	// if server not returned uid.
	if (!$last_uid) return false;

	// return uid.
	return $last_uid;
}

/**
 * Updates the current message.
 *
 * @return $local_message_id - id of updated message
 * Calls a user callback function with local_chat_id.
 *
 * Parameters:
 * @param $uid - uid who message will send,
 * @param $owner_id - owner id of sent message
 * @param $additional - params for message. result of parse_id_to_string() function call
 * @param $params - array with params: text, fwd, attachments
 * @param $callback - function which can called for sending end.
*/
function edit_message ($connection, $uid, $owner_id, $additional, $params, $callback = false)
{
	$text        = '';
	$attachments = [];
	$forwarded   = [];

	// if text has...
	if (!is_empty($params["text"])) $text = trim($params["text"]);

	// if text is too long
	if (strlen($text) > 4096) return ['error' => 101];

	// tmp arrays.
	$fwd_credentials = [];
	$att_credentials = [];

	// if attachments has - parse it.
	if (!is_empty($params["attachments"]))
	{
		$attachments_done = (new AttachmentsParser())->getObjects($params["attachments"]);
		foreach ($attachments_done as $index => $attachment)
		{
			$attachments[]     = $attachment->toArray();
			$att_credentials[] = $attachment->getCredentials();
		}
	}

	$att_credentials = implode(',', $att_credentials);

	// if fwd has - parse it.
	if (!is_empty($params["fwd"]))
	{
		$fwds = explode('_', $params["fwd"]);
		if (count($fwds) > 0)
		{
			$ids   = explode(",", $fwds[0]);
			$cdata = parse_id_from_string($fwds[1]);
			if ($cdata && count($ids) > 0 && count($ids) < 100)
			{
				$uid_local = get_uid_by_lid($connection, $cdata["chat_id"], $cdata["is_bot"], $owner_id);
				if ($uid_local)
				{
					$unique = [];
					foreach ($ids as $key => $value)
					{
						$id = intval($value);
						if ($id > 0 && !in_array($id, $unique) && count($unique) <= 100)
						{
							$unique[] = $id;
						}
					}

					foreach ($unique as $key => $value) {
						$id      = intval($value);
						$message = get_message_array_by_credentials($connection, $uid_local, $id);
						if ($message)
						{
							$forwarded[]       = message_to_array($connection, $message, $additional);
							$fwd_credentials[] = $id;
						}
					}

					$fwd_credentials = implode(',', $fwd_credentials).'_'.$uid_local;
				}
			}
		}
	}

	// after parsing - if empty data - send error
	if (count($attachments) <= 0 && count($forwarded) <= 0 && is_empty($text)) return ['error' => 102];

	// if empty default params - restore it.
	if (is_array($fwd_credentials)) $fwd_credentials = '';
	if (is_array($att_credentials)) $att_credentials = '';

	// if dialog is empty and permissions allow to send message - create new dialog.
	if (!$uid && intval($additional["chat_id"]) > 0)
	{
		return ['error' => 105];
	}

	// if send to multi-chat and chat not exists
	if (!$uid && intval($additional["chat_id"]) < 0)
	{
		return ['error' => 105];
	}

	// multi-chat not have destinatons ids.
	if ($uid < 0) $to_id = 0;

	if ($uid > 0)
	{
		// setting up destination id.
		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"]) $to_id = intval($additional["chat_id"])*-1;
	}

	// get new local id and call user callback.
	$local_chat_id = intval($params['message_id']) > 0 ? intval($params['message_id']) : NULL;
	if (!$local_chat_id) return ['error' => 107];

	$message = message_to_array($connection, get_message_array_by_credentials($connection, $uid, $local_chat_id), $additional);	
	if (!$message) return ['error' => 107];

	if ($message['from_id'] !== intval($owner_id)) return ['error' => 108];

	call_user_func($callback, $local_chat_id);
	// ALL DATA IS OK - UPDATING MESSAGE!!!!
	$connection->prepare("UPDATE messages.chat_engine_1 SET is_edited = 1 WHERE uid = ? AND local_chat_id = ? AND owner_id = ? LIMIT 1;")->execute([intval($uid), intval($local_chat_id), intval($owner_id)]);

	// updating text
	$res = $connection->prepare("UPDATE messages.chat_engine_1 SET text = :new_text WHERE uid = :uid AND local_chat_id = :lid AND owner_id = :owner_id LIMIT 1;");

	$res->bindParam(":new_text", $text,          PDO::PARAM_STR);
	$res->bindParam(":uid",      $uid,           PDO::PARAM_INT);
	$res->bindParam(":lid",      $local_chat_id, PDO::PARAM_INT);
	$res->bindParam(":owner_id", $owner_id,      PDO::PARAM_INT);
	if (!$res->execute()) return ['error' => 105];

	// updating attachments
	$res = $connection->prepare("UPDATE messages.chat_engine_1 SET reply = :attachments WHERE uid = :uid AND local_chat_id = :lid AND owner_id = :owner_id LIMIT 1;");

	$res->bindParam(":attachments", $att_credentials, PDO::PARAM_STR);
	$res->bindParam(":uid",         $uid,             PDO::PARAM_INT);
	$res->bindParam(":lid",         $local_chat_id,   PDO::PARAM_INT);
	$res->bindParam(":owner_id",    $owner_id,        PDO::PARAM_INT);
	if (!$res->execute()) return ['error' => 105];

	// updating fwd data
	$res = $connection->prepare("UPDATE messages.chat_engine_1 SET attachments = :fwd WHERE uid = :uid AND local_chat_id = :lid AND owner_id = :owner_id LIMIT 1;");

	$res->bindParam(":fwd",         $fwd_credentials, PDO::PARAM_STR);
	$res->bindParam(":uid",         $uid,             PDO::PARAM_INT);
	$res->bindParam(":lid",         $local_chat_id,   PDO::PARAM_INT);
	$res->bindParam(":owner_id",    $owner_id,        PDO::PARAM_INT);
	if (!$res->execute()) return ['error' => 105];

	$event = [
		'event'   => 'edit_message',
		'message' => [
			'from_id'     => $owner_id,
			'is_edited'   => true,
			'id'          => $local_chat_id,
			'text'        => $text,
			'time'        => time(),
			'attachments' => $attachments,
			'fwd'         => $forwarded
		]
	];

	if ($uid > 0)
	{
		$user_ids = [$owner_id, $to_id];
		$lids     = [$to_id, $owner_id];

		if ($owner_id === $to_id)
		{
			$user_ids = [$owner_id];
			$lids     = [$owner_id];
		}

		if (($owner_id < 0 || $to_id < 0) && $uid > 0)
		{
			if ($owner_id < 0)
				$event["bot_peer_id"] = $owner_id;

			if ($to_id < 0)
				$event["bot_peer_id"] = $to_id;
		}
	}
	else
	{
		$chat_data = get_chat_info($connection, $uid);

		$user_ids = $chat_data["members"];
		$lids     = $chat_data["local_chat_ids"];
	}

	$event['uid'] = $uid;
	emit_event($user_ids, $lids, $event, $owner_id);

	// OK!!!!
	return $local_chat_id;
}

/**
 * Sends a message.
 *
 * @return $local_chat_id - id of sent message.
 * Calls a user callback function with local_chat_id.
 *
 * Parameters:
 * @param $uid - uid who message will send,
 * @param $owner_id - owner id of sent message
 * @param $additional - params for message. result of parse_id_to_string() function call
 * @param $params - array with params: text, fwd, attachments
 * @param $callback - function which can called for sending end.
*/
function send_message ($connection, $uid, $owner_id, $additional = [], $params, $callback = false)
{
	$text        = '';
	$attachments = [];
	$forwarded   = [];
	$curr_time   = time();

	// if text has...
	if (!is_empty($params["text"])) $text = trim($params["text"]);

	// if text is too long
	if (strlen($text) > 4096) return ['error' => 101];

	// tmp arrays.
	$fwd_credentials = [];
	$att_credentials = [];

	// if attachments has - parse it.
	if (!is_empty($params["attachments"]))
	{
		$attachments_done = (new AttachmentsParser())->getObjects($params["attachments"]);
		foreach ($attachments_done as $index => $attachment)
		{
			$attachments[]     = $attachment->toArray();
			$att_credentials[] = $attachment->getCredentials();
		}
	}

	$att_credentials = implode(',', $att_credentials);

	// if fwd has - parse it.
	if (!is_empty($params["fwd"]))
	{
		$fwds = explode('_', $params["fwd"]);
		if (count($fwds) > 0)
		{
			$ids   = explode(",", $fwds[0]);
			$cdata = parse_id_from_string($fwds[1]);
			if ($cdata && count($ids) > 0 && count($ids) < 100)
			{
				$uid_local = get_uid_by_lid($connection, $cdata["chat_id"], $cdata["is_bot"], $owner_id);
				if ($uid_local)
				{
					$unique = [];
					foreach ($ids as $key => $value)
					{
						$id = intval($value);
						if ($id > 0 && !in_array($id, $unique) && count($unique) <= 100)
						{
							$unique[] = $id;
						}
					}

					foreach ($unique as $key => $value) {
						$id      = intval($value);
						$message = get_message_array_by_credentials($connection, $uid_local, $id);
						if ($message)
						{
							$forwarded[]       = message_to_array($connection, $message, $additional);
							$fwd_credentials[] = $id;
						}
					}

					$fwd_credentials = implode(',', $fwd_credentials).'_'.$uid_local;
				}
			}
		}
	}

	// after parsing - if empty data - send error
	if (count($attachments) <= 0 && count($forwarded) <= 0 && is_empty($text)) return ['error' => 102];

	// if empty default params - restore it.
	if (is_array($fwd_credentials)) $fwd_credentials = '';
	if (is_array($att_credentials)) $att_credentials = '';

	// if keybaord present
	if ($params["keyboard"] !== NULL)
	{
		// keyboard can sent only by bot
		if ($owner_id > 0) return ['error'=>120];

		$keyboard = json_decode($params["keyboard"], true);

		// keyboard must be valid json
		if (!$keyboard) return ['error'=>121];

		$keyboard = parse_keyboard($keyboard);
		
		// if not valid keyboard
		if ($keyboard[0] === false) return ['error'=>122, 'data'=>[$keyboard[1], $keyboard[2]]];

		// hide keyboard if empty
		if (!isset($keyboard['keyboard']))
			$keyboard = "hide";
	}

	// if dialog is empty and permissions allow to send message - create new dialog.
	if (!$uid && intval($additional["chat_id"]) > 0)
	{
		$uid = get_last_uid() + 1;
		if (!$uid) return ['error' => 105];

		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"]) $to_id = intval($additional["chat_id"])*-1;

		$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?);')->execute([$owner_id, $to_id, $uid, $curr_time]);
		$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?);')->execute([$to_id, $owner_id, $uid, $curr_time]);
	}

	// if send to multi-chat and chat not exists
	if (!$uid && intval($additional["chat_id"]) < 0)
	{
		return ['error' => 103];
	}

	// multi-chat not have destinatons ids.
	if ($uid < 0) $to_id = 0;

	if ($uid > 0)
	{
		// setting up destination id.
		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"]) $to_id = intval($additional["chat_id"])*-1;
	}

	// get new local id and call user callback.
	$local_chat_id = intval(get_local_chat_id($uid));
	call_user_func($callback, $local_chat_id);

	// all is ok. Now save message and send event.
	$res = $connection->prepare("
		INSERT INTO 
			messages.chat_engine_1 
		(
			uid, 
			owner_id, 
			local_chat_id, 
			text, 
			attachments, 
			reply, 
			time, 
			flags, 
			to_id
			".($keyboard !== "hide" ? ", keyboard" : "")."
		) VALUES (
			:uid, :owner_id, :local_chat_id, :text, :fwd, :attachments, :time, 0, :to_id".($keyboard !== "hide" ? ", :keyboard" : "").");
	");

	$res->bindParam(":uid",           $uid,             PDO::PARAM_INT);
	$res->bindParam(":owner_id",      $owner_id,        PDO::PARAM_INT);
	$res->bindParam(":local_chat_id", $local_chat_id,   PDO::PARAM_INT);
	$res->bindParam(":text",          $text,            PDO::PARAM_STR);
	$res->bindParam(":fwd",           $fwd_credentials, PDO::PARAM_STR);
	$res->bindParam(":attachments",   $att_credentials, PDO::PARAM_STR);
	$res->bindParam(":time",          $curr_time,       PDO::PARAM_INT);
	$res->bindParam(":to_id",         $to_id,           PDO::PARAM_INT);
	if ($keyboard !== "hide")
	{
		$encoded_keyboard = json_encode($keyboard);

		$res->bindParam(":keyboard", $encoded_keyboard, PDO::PARAM_STR);
	}

	$result = $res->execute();
	if ($result)
	{
		$event = [
			'event'   => 'new_message',
			'message' => [
				'from_id'     => $owner_id,
				'id'          => $local_chat_id,
				'type'        => 'message',
				'text'        => $text,
				'time'        => $curr_time,
				'attachments' => $attachments,
				'fwd'         => $forwarded
			]
		];

		if ($params['payload'] && strlen($params['payload']) <= 1000)
			$event['payload'] = $params['payload'];

		if ($keyboard['keyboard'])
			$event['message']['keyboard'] = $keyboard;

		if ($keyboard === "hide")
		{
			$event['message']['keyboard'] = [];
		}

		if ($uid > 0)
		{
			$user_ids = [$owner_id, $to_id];
			$lids     = [$to_id, $owner_id];

			if ($owner_id === $to_id)
			{
				$user_ids = [$owner_id];
				$lids     = [$owner_id];
			}

			if (($owner_id < 0 || $to_id < 0) && $uid > 0)
			{
				if ($owner_id < 0)
					$event["bot_peer_id"] = $owner_id;

				if ($to_id < 0)
					$event["bot_peer_id"] = $to_id;
			}
		}
		else
		{
			$chat_data = get_chat_info($connection, $uid);

			$user_ids = $chat_data["members"];
			$lids     = $chat_data["local_chat_ids"];
		}

		$event['uid'] = $uid;
		emit_event($user_ids, $lids, $event, $owner_id);
	}

	if ($keyboard === "hide")
	{
		$connection->prepare("UPDATE messages.members_chat_list SET keyboard_created = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 AND user_id > 0;")->execute([intval($uid)]);
	} else
	{
		if ($keyboard["keyboard"])
		{
			$connection->prepare("UPDATE messages.members_chat_list SET keyboard_created = 1 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 AND user_id > 0;")->execute([intval($uid)]);
			$connection->prepare("UPDATE messages.members_chat_list SET keyboard_onetime = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 AND user_id > 0;")->execute([$keyboard["params"]["oneTime"] ? 1 : intval($local_chat_id), intval($uid)]);
		} else 
		{

			$res = $connection->prepare("SELECT keyboard_onetime FROM messages.members_chat_list WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 AND user_id > 0 LIMIT 1;");
			$res->execute([intval($uid)]);

			$onetime = intval($res->fetch(PDO::FETCH_ASSOC)["keyboard_onetime"]);
			if ($onetime === 1 || $keyboard === "hide")
				$connection->prepare("UPDATE messages.members_chat_list SET keyboard_created = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0 AND user_id > 0;")->execute([intval($uid)]);
		}
	}

	$connection->prepare("UPDATE messages.members_chat_list SET last_time = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([time(), intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET hidden = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET is_read = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([intval($uid)]);

	if ($owner_id > 0 && $additional['is_bot'])
	{
		toggle_send_access($connection, $owner_id, $additional['chat_id']);
	}

	read_chat($connection, $uid, $owner_id, $additional);
	return $local_chat_id;
}

/**
 * Read the chat by selected uid for
 * @return true if ok or false if error.
 *
 * Parameters:
 * @param $uid - uid of chat wh must be read.
 * @param $owner_id - user_id who reads the chat
 * @param result or parse_id_to_string() function call.
*/
function read_chat ($connection, $uid, $owner_id, $additional)
{
	// selecting chats info.
	// DEFAULT PARAMS
	$leaved_time  = 0;
	$return_time  = 0;
	$is_leaved    = 0;
	$is_kicked    = 0;
	$last_message = 0;

	$res = $connection->prepare("SELECT is_read, is_leaved, is_kicked, return_time, leaved_time FROM messages.members_chat_list WHERE uid = ? AND user_id = ? LIMIT 1;");
	$res->execute([$uid, $owner_id]);

	// selecting data of this chat.
	$result = $res->fetch(PDO::FETCH_ASSOC);

	// chat not exists!!!
	if (!$result) return false;
	if (intval($result['is_read'])) return false;

	// setting new params
	$is_leaved   = intval($result["is_leaved"]);
	$is_kicked   = intval($result["is_kicked"]);
	$return_time = intval($result["return_time"]);
	$leaved_time = intval($result["leaved_time"]);

	// setting up last_message_id;
	$res = $connection->prepare(get_chat_query($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, $user_id, true, 0)); 
	$res->execute([$user_id]);

	$local_chat_id = intval($res->fetch(PDO::FETCH_ASSOC)['local_chat_id']);

	$connection->prepare("UPDATE messages.members_chat_list SET last_read_message_id = ? WHERE user_id = ? AND uid = ? LIMIT 1000;")->execute([intval($local_chat_id), intval($owner_id), intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET hidden = 0 WHERE uid = ? AND user_id = ? AND is_leaved = 0 AND is_kicked = 0 LIMIT 1000;")->execute([intval($uid), intval($owner_id)]);
	$connection->prepare("UPDATE messages.members_chat_list SET is_read = 1 WHERE user_id = ? AND uid = ? LIMIT 1000;")->execute([intval($owner_id), intval($uid)]);

	// bot is not read the chats.
	if (intval($owner_id) <= 0) return true;	
	$event = ['event' => 'dialog_read'];

	// set up peer_id
	$additional["is_bot"] ? ($event['bot_peer_id'] = intval($additional["chat_id"])) : ($event['peer_id'] = intval($additional["chat_id"]));

	// dialog readed - send event.
	return emit_event([$owner_id], [0], $event);
}

/**
 * Code of this function is very similar for send_message()!
*/
function send_service_message ($connection, $uid, $owner_id, $message_type, $additional = [], $callback = false)
{
	$curr_time      = time();
	$allowed_events = [
		"mute_user", "unmute_user", "returned_to_chat",
		"join_by_link", "leaved_chat", "updated_photo",
		"deleted_photo", "kicked_user", "invited_user",
		"change_title", "chat_create"
	];

	// if requested event is not allowed - false;
	if (!in_array(strtolower($message_type), $allowed_events)) return false;

	if (!$uid && intval($additional["chat_id"]) > 0)
	{
		$uid = get_last_uid();
		if (!$uid)
			return [
				'error' => 105
			];

		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"])
			$to_id = intval($additional["chat_id"])*-1;

		$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?);')->execute([$owner_id, $to_id, $uid, $curr_time]);
		$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, last_time) VALUES (?, ?, ?, 0, ?);')->execute([$to_id, $owner_id, $uid, $curr_time]);
	}
	if (!$uid && intval($additional["chat_id"]) < 0)
	{
		return [
			'error' => 103
		];
	}

	if ($uid < 0)
		$to_id = 0;

	if ($uid > 0)
	{
		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"])
			$to_id = intval($additional["chat_id"])*-1;
	}

	$local_chat_id = intval(get_local_chat_id($uid));
	call_user_func($callback, $local_chat_id);

	$res = $connection->prepare("
		INSERT INTO 
			messages.chat_engine_1 
		(
			uid, 
			owner_id, 
			local_chat_id,
			time, 
			flags, 
			to_id, 
			event, 
			new_src, 
			new_title
		) VALUES (
			:uid, :owner_id, :local_chat_id, :time, 0, :to_id, :event, :new_src, :new_title
		);
	");

	$new_src   = "";
	$new_title = "";

	if ($additional["new_src"])
		$new_src = $additional["new_src"];
	if ($additional["new_title"])
		$new_title = $additional["new_title"];

	$actioner_id = intval($additional["actioner_id"]);
	$new_query   = strval($additional["new_query"]);

	$res->bindParam(":uid",           $uid,           PDO::PARAM_INT);
	$res->bindParam(":owner_id",      $owner_id,      PDO::PARAM_INT);
	$res->bindParam(":local_chat_id", $local_chat_id, PDO::PARAM_INT);
	$res->bindParam(":time",          $curr_time,     PDO::PARAM_INT);
	$res->bindParam(":to_id",         $actioner_id,   PDO::PARAM_INT);
	$res->bindParam(":event",         $message_type,  PDO::PARAM_STR);
	$res->bindParam(":new_src",    	  $new_query,     PDO::PARAM_STR);
	$res->bindParam(":new_title",     $new_title,     PDO::PARAM_STR);

	if ($res->execute())
	{
		$event = [
			'event' => 'new_message',
			'message' => [
				'from_id' => $owner_id,
				'id'      => $local_chat_id,
				'type'    => 'service_message',
				'time'    => $curr_time,
				'action' => [
					'type' => $message_type,
				]
			]
		];

		if (intval($additional["actioner_id"]) !== 0);
			$event["message"]["action"]["to_id"] = intval($additional["actioner_id"]);

		if ($new_src !== "")
			$event["message"]["action"]["new_photo_url"] = $new_src;

		if ($new_title !== "")
			$event["message"]["action"]["new_title"] = $new_title;

		if ($uid > 0)
		{
			$user_ids = [$owner_id, $to_id];
			$lids     = [$to_id, $owner_id];

			if (($owner_id < 0 || $to_id < 0) && $uid > 0)
			{
				if ($owner_id < 0)
					$event["bot_peer_id"] = $owner_id;

				if ($to_id < 0)
					$event["bot_peer_id"] = $to_id;
			}
		}
		else
		{
			$chat_data = get_chat_info($connection, $uid);

			$user_ids = $chat_data["members"];
			$lids     = $chat_data["local_chat_ids"];
		}
		emit_event($user_ids, $lids, $event, $owner_id);
	}

	$connection->prepare("UPDATE messages.members_chat_list SET last_time = ? WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([time(), intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET hidden = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([intval($uid)]);
	$connection->prepare("UPDATE messages.members_chat_list SET is_read = 0 WHERE uid = ? AND is_leaved = 0 AND is_kicked = 0;")->execute([intval($uid)]);

	return $local_chat_id;
}

/**
 * Delete messages for all or only for selected user
 * @return array with array like ($message_id => $delete_state);
 *
 * Parameters:
 * @param $uid - uid of chat from where messages must be deleted
 * @param $deleter_id - user_id who deletes the message
 * @param $message_ids - array with message ids.
 * @param $delete_for_all - flag who sets delete for all or not
 * @param $additional - result of parse_id_from_string() function call
 * @param $permissions - Permissions class instance
 * @param $me - object of current user in chat.
*/
function delete_messages ($connection, $uid, $deleter_id, $message_ids, $delete_for_all = 0, $additional = [], $permissions = null, $me = [])
{
	// deletion states
	$NOT_DELETED = 0;
	$DELETION_OK = 1;

	// selecting unique messaging ids.
	$messages = [];
	foreach ($message_ids as $index => $id) {
		if (intval($id) !== 0)
			$messages[] = intval($id);
	}

	// done states.
	$result = [];
	foreach ($messages as $index => $message_id)
	{
		// selecting message.
		$res = $connection->prepare('SELECT owner_id, deleted_for FROM messages.chat_engine_1 WHERE (deleted_for NOT LIKE "%'.intval($deleter_id).',%" OR deleted_for IS NULL) AND deleted_for_all != 1 AND local_chat_id = ? AND uid = ?;');
		$res->execute([intval($message_id), intval($uid)]);
		$data = $res->fetch(PDO::FETCH_ASSOC);

		// checking owner_id
		$owner_id = intval($data["owner_id"]);

		// if not message exists.
		if (!$owner_id)
		{
			$result[strval($message_id)] = $NOT_DELETED;
			continue;
		}

		// checking in chat
		if ($uid > 0)
		{
			// for deletion you must be owner of message in dialog.
			if ($owner_id !== $deleter_id && $delete_for_all)
			{
				$result[strval($message_id)] = $NOT_DELETED;
				continue;
			}
		} else
		{
			// in multi-chat you must have permission to delete other messages
			if ($owner_id !== $deleter_id && $delete_for_all)
			{
				if ($permissions->getValue("delete_messages_2") > $me["flags"]["level"])
				{
					$result[strval($message_id)] = $NOT_DELETED;
					continue;
				}
			}
		}

		// here we can delete message.
		$result[strval($message_id)] = $DELETION_OK;

		// delete for all
		if ($delete_for_all)
		{
			$connection->prepare("UPDATE messages.chat_engine_1 SET deleted_for_all = 1 WHERE local_chat_id = ? AND uid = ?;")->execute([intval($message_id), intval($uid)]);
		}
		else
		{

			// delete for me only.
			$deleted_for = strval($data["deleted_for"]);
			$deleted_for .= intval($deleter_id).',';

			$res = $connection->prepare("UPDATE messages.chat_engine_1 SET deleted_for = :deleted_for WHERE local_chat_id = :local_chat_id AND uid = :uid;");

			$res->bindParam(":deleted_for",   $deleted_for, PDO::PARAM_STR);
			$res->bindParam(":uid",           $uid,         PDO::PARAM_INT);
			$res->bindParam(":local_chat_id", $message_id,  PDO::PARAM_INT);
			$res->execute();
		}
	}

	// send event.
	$event = [
		'event'       => 'message_delete',
		'message_ids' => $result
	];

	if ($uid < 0) $to_id = 0;
	$user_ids = [];
	$lids     = [];

	if ($delete_for_all)
	{
		if ($uid > 0)
		{
			$to_id = intval($additional["chat_id"]);
			if ($additional["is_bot"])
				$to_id = intval($additional["chat_id"])*-1;

			$user_ids = [$deleter_id, $to_id];
			$lids     = [$to_id, $deleter_id];

			if (($deleter_id < 0 || $to_id < 0) && $uid > 0)
			{
				if ($deleter_id < 0)
					$event["bot_peer_id"] = $deleter_id;

				if ($to_id < 0)
					$event["bot_peer_id"] = $to_id;
			}
		}
		else
		{
			$chat_data = get_chat_info($connection, $uid);

			$user_ids = $chat_data["members"];
			$lids     = $chat_data["local_chat_ids"];
		}
	} else {
		$user_ids[] = $deleter_id;
		if ($deleter_id < 0)
			$event["bot_peer_id"] = $deleter_id;
		if ($to_id < 0)
			$event["bot_peer_id"] = $to_id;

		$to_id = intval($additional["chat_id"]);
		if ($additional["is_bot"])
			$to_id = intval($additional["chat_id"])*-1;

		$lids[] = $to_id;
	}

	emit_event($user_ids, $lids, $event, $deleter_id);
	return $result;
}

/**
 * Toggle permission to sends message
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - user_id who allows messages
 * @param $bot_id  - for who allow messages
 * @param $allow   - true if allow or false if disallow
*/
function toggle_send_access ($connection, $user_id, $bot_id, $allow = true)
{
	// getting current state
	$res = $connection->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1;");
	$res->execute([intval($user_id), intval($bot_id)]);
	$data = $res->fetch(PDO::FETCH_ASSOC)['state'];
	if ($data === NULL)
	{
		$connection->prepare("INSERT INTO users.bot_relations (user_id, bot_id, state) VALUES (?, ?, -1);")->execute([intval($user_id), intval($bot_id)]);
	}

	if ($allow)
	{
		return $connection->prepare("UPDATE users.bot_relations SET state = 1 WHERE user_id = ? AND bot_id = ? LIMIT 1;")->execute([intval($user_id), intval($bot_id)]);
	} else
	{
		return $connection->prepare("UPDATE users.bot_relations SET state = -1 WHERE user_id = ? AND bot_id = ? LIMIT 1;")->execute([intval($user_id), intval($bot_id)]);
	}
}

/**
 * Checks the messaging write allowance
 * @return true if allow or false if not
 *
 * Parameters:
 * @param $user_id - user id who state must be checked
 * @param $bot_id - bot id for who must check
*/
function is_chat_allowed ($connection, $user_id, $bot_id)
{
	// getting state
	$res = $connection->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1;");
	if ($res->execute([intval($user_id), intval($bot_id)]))
	{
		$data = $res->fetch(PDO::FETCH_ASSOC);
		if ($data)
		{
			$state = intval($data['state']);

			// state -1 is false for user.
			if ($state !== -1) return true;
		}
	}

	// errors?
	return false;
}

?>