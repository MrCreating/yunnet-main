<?php

namespace unt\functions\users;


// connecting modules.
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Entity;
use unt\objects\Notification;
use unt\objects\User;
use unt\platform\DataBaseManager;
use unt\platform\EventEmitter;

/**
 * This file contains a user's functions
*/

// sends a friendship request to $user_id from $owner_id
function create_friendship ($connection, $owner_id, $user_id)
{
	if ($owner_id === $user_id) return false;

	$FRIENDS   = 2;
	$REQUESTED = 1;
	$UNKNOWN   = 0;

	// getting current state.
	$res = DataBaseManager::getConnection()->prepare("SELECT id, user1, user2, state FROM users.relationships WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;");
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
				Notification::create($user_id, "friendship_accepted", [
					'user_id' => intval($owner_id)
				]);

                $emitter = new EventEmitter();
                $emitter->sendEvent([$owner_id], [0], [
                    'event'   => 'friendship_by_me_accepted',
                    'user_id' => intval($user_id)
                ]);

				DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET state = 2 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
				DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET is_hidden = 0 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);

				return true;
			}
		}

		if ($state === $UNKNOWN)
		{
			DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET user1 = ? WHERE id = ? LIMIT 1;")->execute([intval($owner_id), intval($friendship['id'])]);
			DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET user2 = ? WHERE id = ? LIMIT 1;")->execute([intval($user_id), intval($friendship['id'])]);
			DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET is_hidden = 0 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
			
			Notification::create($user_id, "friendship_requested", [
				'user_id' => intval($owner_id)
			]);

			return DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET state = 1 WHERE id = ? LIMIT 1;")->execute([intval($friendship['id'])]);
		}
	} else 
	{
		Notification::create($user_id, "friendship_requested", [
			'user_id' => intval($owner_id)
		]);

		return DataBaseManager::getConnection()->prepare("INSERT INTO users.relationships (user1, user2, state) VALUES (?, ?, ?);")->execute([
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
function delete_friendship ($connection, $owner_id, $user_id): bool
{
	if ($owner_id === $user_id)
		return false;

	$res = DataBaseManager::getConnection()->prepare("SELECT user1, user2, state FROM users.relationships WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;");
	$res->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
	$friendship = $res->fetch(PDO::FETCH_ASSOC);

	if (!$friendship)
		return false;

	$initiator = intval($friendship["user1"]);
	$resolver  = intval($friendship["user2"]);
	$state     = intval($friendship["state"]);

	if ($state === 2)
	{
		DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET user1 = ? WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($user_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET user2 = ? WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET is_hidden = 1 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);

		Notification::create($user_id, "deleted_friend", [
			'user_id' => intval($owner_id)
		]);

		return DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET state = 1 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
	}
	if ($state === 1)
	{
		if ($owner_id === $initiator)
		{
			return DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET state = 0 WHERE user1 = ? AND user2 = ? OR user1 = ? AND user2 = ?;")->execute([intval($owner_id), intval($user_id), intval($user_id), intval($owner_id)]);
		}
	}

	return false;
}

// getting friends or subscribers or outcoming requests list.
function get_friends_list ($connection, $user_id, $section = "friends", $extended = 1): array
{
	return User::findById($user_id)->getFriendsList($section, $extended);
}

// get cunters for come user
function get_counters ($connection, $user_id): array
{
	$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT COUNT(local_id) FROM users.notes WHERE is_read = 0 AND is_hidden = 0 AND owner_id = ?;");
	$res->execute([$user_id]);
	$notes_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(local_id)"]);
	
	$res = DataBaseManager::getConnection()->prepare("SELECT COUNT(DISTINCT uid) FROM messages.members_chat_list WHERE is_read = 0 AND hidden = 0 AND user_id = ?;");
	$res->execute([$user_id]);
	$messages_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(DISTINCT uid)"]);

	$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT COUNT(id) FROM users.relationships WHERE state = 1 AND is_hidden = 0 AND user2 = ? AND user1 != user2;");
	$res->execute([$user_id]);
	$friends_count = intval($res->fetch(PDO::FETCH_ASSOC)["COUNT(id)"]);

    return [
        'messages'      => $messages_count,
        'notifications' => $notes_count,
        'friends'       => $friends_count
    ];
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
]): array

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
	$res = DataBaseManager::getConnection()->prepare($query_call);
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

		foreach ($data as $user_id) {
			$user_id = $user_id["id"];

			if (!in_array(intval($user_id), $temp))
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
	$res = DataBaseManager::getConnection()->prepare("SELECT user1, user2, state, is_hidden FROM users.relationships WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;");
	$res->execute([intval($user_id), intval($check_id), intval($check_id), intval($user_id)]);

	// result of.
	$data = $res->fetch(\PDO::FETCH_ASSOC);

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
	$user = new User(intval($select_id));

	return $user->canAccessClosed();
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
    $emitter = new EventEmitter();
    $emitter->sendEvent([$user_id], [0], [
        'event'   => 'request_hide',
        'user_id' => intval($hide_id)
    ]);

	return DataBaseManager::getConnection()->prepare("UPDATE users.relationships SET is_hidden = 1 WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?) LIMIT 1;")->execute([intval($user_id), intval($hide_id), intval($hide_id), intval($user_id)]);
}
?>