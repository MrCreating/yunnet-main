<?php

/**
 * Functions to manage user profiles.
 *
 * @throws ADMIN FEATURES
*/

/**
 * Ban or unbans the user
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - id who must be banne
*/
function ban ($connection, $user_id)
{
	// getting the cache for LP and services
	$cache = get_cache();

	// checking current state
	$res = $connection->prepare("SELECT is_banned FROM ".(intval($user_id) > 0 ? "users.info" : "bots.info")." WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id) > 0 ? intval($user_id) : intval($user_id)*-1]);

	// current ban state
	$is_banned = intval($res->fetch(PDO::FETCH_ASSOC)['is_banned']);

	// block or unblock lp.
	$cache->set('banned_'.intval($user_id) > 0 ? intval($user_id) : intval($user_id)*-1, strval($is_banned));

	// doing ban or unban
	return $connection->prepare("UPDATE ".(intval($user_id) < 0 ? "bots.info" : "users.info")." SET is_banned = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([intval(!$is_banned), intval($user_id) > 0 ? intval($user_id) : intval($user_id)*-1]);
}

/**
 * Setting new user level for user
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - which level must be changed?
*/
function set_user_level ($connection, $user_id, $new_level = 0)
{
	// only from 0 to 4 levels
	if (intval($new_level) < 0 || intval($new_level) > 3) return false;

	// setting new level
	return $connection->prepare("UPDATE users.info SET userlevel = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([intval($new_level), intval($user_id)]);
}

/**
 * Sets the cookies count
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - for who set?
 * @param $group_id = 1 || 2 - full cookies or bite?
 * @param $count - count of cookies. Must be positive
*/
function set_user_cookies ($connection, $user_id, $group_id, $count)
{
	// only 2 groups!
	if ($group_id > 2 || $group_id < 1) return false;

	// col name
	$column_name = $group_id === 1 ? "cookies" : "half_cookies";

	// setting new amount.
	return $connection->prepare("UPDATE users.info SET ".$column_name." = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([intval($count), intval($user_id)]);
}

/**
 * Deletes user or bot from this social network
 * USE IT FOR YOUR ATTENTION!!!
 * @return true if success
 *
 * Parameters:
 * @param $user_id - deletion user or bot id.
*/
function delete_user ($connection, $user_id)
{
	$res = $connection->prepare("UPDATE " . ($user_id > 0 ? "users.info" : "bots.info") . " SET is_deleted = 1 WHERE id = ? LIMIT 1");

	if ($res->execute([$user_id > 0 ? $user_id : ($user_id * -1)]))
	{
		return true;
	}

	return false;
}

/**
 * Checks if project is closed
 * @return true if yes
*/
function is_project_closed ()
{
	$mem = new Memcached();
	$mem->addServer('127.0.0.1', 11211);

	$result = boolval(intval($mem->get('closed_project')));

	return $result;
}

/**
 * Checks if registration is closed
 * @return true if yes
*/
function is_register_closed ()
{
	$mem = new Memcached();
	$mem->addServer('127.0.0.1', 11211);

	$result = boolval(intval($mem->get('closed_register')));

	return $result;
}

// closes the project
function toggle_project_close ()
{
	$result = is_project_closed();

	$mem = new Memcached();
	$mem->addServer('127.0.0.1', 11211);

	$mem->set("closed_project", strval(intval(!$result)));
	return !$result;
}

// toggle the register access
function toggle_register_close ()
{
	$result = is_register_closed();

	$mem = new Memcached();
	$mem->addServer('127.0.0.1', 11211);

	$mem->set("closed_register", strval(intval(!$result)));
	return !$result;
}
?>