<?php
// connecting modules.
require_once __DIR__ . "/notifications.php";

/**
 * This file contains a user's functions
*/

// checks $user_id in the $owner_id's blacklist and returns true or false
function in_blacklist ($connection, $owner_id, $user_id)
{
	return (new User($owner_id))->inBlacklist();
}

// checks if $user_id is friend with a $owner_id, and returns a true, 
function is_friends ($connection, $owner_id, $user_id)
{
	return (new User($owner_id))->isFriends();
}

// add $user_id to $owner_id's blacklist or remove it.
function block_user ($connection, $owner_id, $user_id)
{
	return Context::get()->getCurrentUser()->block($user_id);
}

// sends a friendship request to $user_id from $owner_id
function create_friendship ($connection, $owner_id, $user_id)
{
	if ($owner_id === $user_id) return false;

	$FRIENDS   = 2;
	$REQUESTED = 1;
	$UNKNOWN   = 0;

	// getting current state.
	$res = $connection->prepare("SELECT id, user1, user2, state FROM users.relationships WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;");
	$res->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
	$friendship = $res->fetch(PDO::FETCH_ASSOC);

	$friendship_id = intval($friendship['id']);

	// if already has state
	if ($friendship_id)
	{
		$initiator = intval($friendship["user1"]);
		$resolver  = intval($friendship["user2"]);
		$state     = intval($friendship["state"]);

		// friendhsip already created
		if ($state === $FRIENDS)
		{
			return false;
		}

		if ($state === $REQUESTED)
		{
			if ($owner_id === $resolver)
			{
				create_notification($connection, $user_id, "friendship_accepted", [
					'user_id' => intval($owner_id)
				]);

				emit_event([$owner_id], [0], [
					'event'   => 'friendship_by_me_accepted',
					'user_id' => intval($user_id)
				]);

				$connection->prepare("UPDATE users.relationships SET state = 2 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
				$connection->prepare("UPDATE users.relationships SET is_hidden = 0 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);

				return true;
			}
		}

		if ($state === $UNKNOWN)
		{
			$connection->prepare("UPDATE users.relationships SET user1 = ? WHERE id = ? LIMIT 1;")->execute([intval($owner_id), intval($friendship['id'])]);
			$connection->prepare("UPDATE users.relationships SET user2 = ? WHERE id = ? LIMIT 1;")->execute([intval($user_id), intval($friendship['id'])]);
			$connection->prepare("UPDATE users.relationships SET is_hidden = 0 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
			
			create_notification($connection, $user_id, "friendship_requested", [
				'user_id' => intval($owner_id)
			]);

			return $connection->prepare("UPDATE users.relationships SET state = 1 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
		}
	} else 
	{
		create_notification($connection, $user_id, "friendship_requested", [
			'user_id' => intval($owner_id)
		]);

		return $connection->prepare("INSERT INTO users.relationships (user1, user2, state) VALUES (?, ?, ?);")->execute([
			intval($owner_id), intval($user_id), 1
		]);
	}

	return false;
}

/* 
 * deletes or declares the friends requests or friendships 
 * $owner_id  - from wh list must be deleted friend
 * $user_id - who must be deleted?
 */
function delete_friendship ($connection, $owner_id, $user_id)
{
	if ($owner_id === $user_id)
		return false;

	$res = $connection->prepare("SELECT user1, user2, state FROM users.relationships WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;");
	$res->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
	$friendship = $res->fetch(PDO::FETCH_ASSOC);

	if (!$friendship)
		return false;

	$initiator = intval($friendship["user1"]);
	$resolver  = intval($friendship["user2"]);
	$state     = intval($friendship["state"]);

	if ($state === 2)
	{
		$connection->prepare("UPDATE users.relationships SET user1 = ? WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($user_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		$connection->prepare("UPDATE users.relationships SET user2 = ? WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		$connection->prepare("UPDATE users.relationships SET is_hidden = 1 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);

		create_notification($connection, $user_id, "deleted_friend", [
			'user_id' => intval($owner_id)
		]);

		return $connection->prepare("UPDATE users.relationships SET state = 1 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
	}
	if ($state === 1)
	{
		if ($owner_id === $initiator)
		{
			return $connection->prepare("UPDATE users.relationships SET state = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		}
	}

	return false;
}

// getting friends or subscribers or outcoming requests list.
function get_friends_list ($connection, $user_id, $section = "friends", $extended = 1)
{
	$res = [];
	switch ($section)
	{
		case "subscribers":
			$res = $connection->prepare("SELECT user1, user2, state FROM users.relationships WHERE user2 = ? AND state = 1 AND user1 != user2 LIMIT 50;");
			$res->execute([intval($user_id)]);
		break;
		case "outcoming":
			$res = $connection->prepare("SELECT user1, user2, state FROM users.relationships WHERE user1 = ? AND state = 1 AND user1 != user2 LIMIT 50;");
			$res->execute([intval($user_id)]);
		break;
		default:
			$res = $connection->prepare("SELECT user1, user2, state FROM users.relationships WHERE (user1 = ? OR user2 = ?) AND state = 2 AND user1 != user2 LIMIT 50;");
			$res->execute([intval($user_id), intval($user_id)]);
		break;
	}

	$result      = [];
	$identifiers = $res->fetchAll(PDO::FETCH_ASSOC);
	foreach ($identifiers as $index => $userdata)
	{
		$user_current = intval($userdata["user1"]);
		if ($user_current === $user_id)
			$user_current = intval($userdata["user2"]);

		if ($extended)
		{
			$user = new User($user_current);
			if (!$user->valid()) continue;

			$result[] = $user;
		}
		else
			$result[] = $user_current;
	}

	return $result;
}

/**
 * Set new profile data
 *
 * Parameters:
 * @param $user_id - user_id which data chang
 * @param $data_type - type of data change. It can be:
 *												"first_name" - first name of user
 *												"last_name"  - last name of user
 *
 * @return true - data changed without problems
 * @return false - no changes made and no errors
 * @return -1 - value has forbidden characters
 * @return -2 - value has length error (short or long)
 * @return -3 - value is empty
*/
function update_user_data ($connection, $user_id, $data_type, $new_value)
{
	switch ($data_type)
	{
		case "first_name":
			return Context::get()->getCurrentUser()->edit()->setFirstName($new_value);
		break;
		case "last_name":
			return Context::get()->getCurrentUser()->edit()->setLastName($new_value);
		break;
		default:
		break;
	}

	return false;
}

// get cunters for come user
function get_counters ($connection, $user_id)
{
	$res = $connection->prepare("SELECT DISTINCT COUNT(local_id) FROM users.notes WHERE is_read = 0 AND is_hidden = 0 AND owner_id = ?;");
	$res->execute([$user_id]);
	$notes_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(local_id)"]);
	
	$res = $connection->prepare("SELECT COUNT(DISTINCT uid) FROM messages.members_chat_list WHERE is_read = 0 AND hidden = 0 AND user_id = ?;");
	$res->execute([$user_id]);
	$messages_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT uid)"]);

	$res = $connection->prepare("SELECT DISTINCT COUNT(id) FROM users.relationships WHERE state = 1 AND is_hidden = 0 AND user2 = ? AND user1 != user2;");
	$res->execute([$user_id]);
	$friends_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(id)"]);

	$result = [
		'messages'      => $messages_count,
		'notifications' => $notes_count,
		'friends'       => $friends_count
	];

	return $result;
}

// set's the user's privacy settings
function set_privacy_settings ($connection, $user_id, $group_id, $new_value = 0)
{
	$groups = [
		1 => 'can_write_messages',
		2 => 'can_write_on_wall',
		3 => 'can_invite_to_chats',
		4 => 'can_comment_posts'
	];

	return Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('privacy')->setGroupValue($groups[$group_id], $new_value)->getGroupValue($groups[$group_id]);
}

// get users who is blacklisted by $user_id
function get_blacklist ($connection, $user_id, $count = 30, $offset = 0)
{
	if ($count < 0) return [];
	if ($offset > 15000000) return [];

	if (!class_exists('Entity'))
		require __DIR__ . "/../objects/entities.php";

	$res = $connection->prepare("SELECT added_id FROM users.blacklist WHERE state = -1 AND user_id = ? LIMIT ".intval($offset).", ".intval($count).";");
	$res->execute([intval($user_id)]);

	$blacklist = $res->fetchAll(PDO::FETCH_ASSOC);
	$result    = [];

	foreach ($blacklist as $index => $id) {
		$user = new User(intval($id["added_id"]));
		if (!$user->valid()) continue;

		$result[] = $user;
	}

	return $result;
}

// set another settings
function set_user_settings ($connection, $user_id, $setting, $new_value = 0)
{
	$push = Context::get()->getCurrentUser()->getSettings()->getSettingsGroup('push');
	if ($setting === 'notifications')
		return $push->setNotificationsEnabled(boolval(intval($new_value)));
	if ($setting === 'sound')
		return $push->setSoundEnabled(boolval(intval($new_value)));
}

/** 
 * search users by query
 * returns array of User and Bot objects
 * or empty array
 */
function search_users ($connection, $query, $additional_params = [
	"search_bots" => false,
	"offset"      => 0,
	"count"       => 50
])

{
	$result = [];

	$query = explode(' ', capitalize(trim($query)));
	if (count($query) > 20 || count($query) < 1)
		return $result;

	$query_call = "SELECT DISTINCT id FROM users.info WHERE ";
	if ($additional_params['only_bots'])
		$query_call = "SELECT DISTINCT id FROM bots.info WHERE ";

	foreach ($query as $index => $word) {
		if (is_empty($word))
			continue;

		if (!$additional_params['only_bots'])
		{
			$only_online = '';
			if ($additional_params['online_only'])
				$only_online = ' AND is_online >= '.(time() - 30);

			if ($index === 0)
				$query_call .= '((id LIKE :id_'.$index.' OR first_name LIKE CONCAT("%", :first_name_'.$index.', "%") OR last_name LIKE CONCAT("%", :last_name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0'.$only_online.')';
			else
				$query_call .= ' OR ((id LIKE :id_'.$index.' OR first_name LIKE CONCAT("%", :first_name_'.$index.', "%") OR last_name LIKE CONCAT("%", :last_name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0'.$only_online.')';
		} else
		{
			if ($index === 0)
				$query_call .= '((id LIKE :id_'.$index.' OR name LIKE CONCAT("%", :name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0)';
			else
				$query_call .= ' OR ((id LIKE :id_'.$index.' OR name LIKE CONCAT("%", :name_'.$index.', "%")) AND is_banned = 0 AND is_deleted = 0)';
		}
	}

	$query_call .= " LIMIT ".intval($additional_params['offset']).",".intval($additional_params["count"]).";";

	// preparing requests
	$res = $connection->prepare($query_call);
	foreach ($query as $index => $word) {
		if (is_empty($word))
			continue;

		if (!$additional_params['only_bots'])
		{
			$res->bindParam(":id_".$index,         $word, PDO::PARAM_INT);
			$res->bindParam(":first_name_".$index, $word, PDO::PARAM_STR);
			$res->bindParam(":last_name_".$index,  $word, PDO::PARAM_STR);
		} else
		{
			$res->bindParam(":id_".$index,         $word, PDO::PARAM_INT);
			$res->bindParam(":name_".$index, $word, PDO::PARAM_STR);
		}
		
	}
	
	if ($res->execute())
	{
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		$temp = [];

		foreach ($data as $index => $user_id) {
			$user_id = $user_id["id"];

			if (!in_array($temp, intval($user_id)))
			{
				$temp[] = intval($user_id);

				$object = new User(intval($user_id));
				if (!$object->valid() || $additional_params['only_bots'])
				{
					$object = new Bot(intval($user_id));
				}

				if (!$object->valid()) continue;

				$result[] = $object;
			}
		}
	}

	return $result;
}

/**
 * Get friendship state
 * @return state 0-2
 *
 * Parameters:
 * $user_id - who check
 * $check_id - with who check user_id 
*/
function get_friendship_state ($connection, $user_id, $check_id)
{
	// always friends! :)
	if ($user_id === $check_id) return 2;

	// selecting user frindship
	$res = $connection->prepare("SELECT user1, user2, state, is_hidden FROM users.relationships WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;");
	$res->execute([intval($user_id), intval($check_id), intval($check_id), intval($user_id)]);

	// result of.
	$data = $res->fetch(PDO::FETCH_ASSOC);

	// return array with state;
	$result = [
		'user1' => intval($data["user1"]),
		'user2' => intval($data["user2"]),
		'state' => intval($data["state"])
	];

	if (intval($data['is_hidden']))
		$result['is_hidden'] = true;

	return $result;
}

/**
 * Checks access by level
 * @return true if $user_id can see data of $select_id or false if not
 *
 * Parameters:
 * @param $user_id - user_id from who must check
 * @param $select_id - who accound check
*/
function can_access_closed ($connection, $user_id, $select_id)
{
	// always can check;
	if ($user_id === $select_id) return true;
	if ($select_id < 0) return true;

	// connecting modules
	if (!class_exists('Entity'))
		require __DIR__ . "/../objects/entities.php";

	$user = new User(intval($select_id));

	// if profile closed.
	if ($user->getSettings()->getSettingsGroup('account')->isProfileClosed())
	{
		// checking friendship state. Must be 2.
		$state = get_friendship_state($connection, $user_id, $select_id)["state"];

		// if not 2
		if ($state !== 2) return false;
	}

	// else ok
	return true;
}

/**
 * Check can you invite this user to a new chat or not
 * @return true/false
 *
 * Parameters:
 * $user_id - current user id.
 * $check_profile - User object with checking profile
*/
function can_invite_to_chat ($connection, $user_id, $check_profile) 
{
	if (!$check_profile->valid()) return false;

	if ($check_profile->type === 'bot') {
		if ($user_id === $check_profile->getOwnerId()) return true;

		$can_inv = $check_profile->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_invite_to_chats');

		if ($can_inv === 0 || $can_inv === 1 || $can_inv === NULL) return true;
	}

	if (is_friends($connection, $user_id, $check_profile->getId()))
	{
		$can_inv = $check_profile->getSettings()->getSettingsGroup('privacy')->getGroupValue('can_invite_to_chats');

		if ($can_inv === 0 || $can_inv === NULL) return true;
	}

	return false;
}

/**
 * Hides the friendship request
 * @return true if ok or false if not.
 *
 * Parameters:
 * @param int $user_id - current user id.
 * @param int $hide_id - user who must be hidden
*/
function hide_friendship_request ($connection, $user_id, $hide_id)
{
	if (!function_exists('emit_event'))
		require __DIR__ . '/../emitters.php';

	emit_event([$user_id], [0], [
		'event'   => 'request_hide',
		'user_id' => intval($hide_id)
	]);

	return $connection->prepare("UPDATE users.relationships SET is_hidden = 1 WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;")->execute([intval($user_id), intval($hide_id), intval($hide_id), intval($user_id)]);
}
?>