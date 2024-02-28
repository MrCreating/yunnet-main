<?php
/**
 *
 * Contains a messages functions (send, get, etc)
 *
*/

use unt\platform\DataBaseManager;
/**
 * Gets last uid of all yunnet dialogs.
 * return last free uid.
 *
 * Parameters:
 * @param $dialog - true if uid for dialog, false if uid needed for multi-chat
*/
function get_last_uid ($dialog = true)
{
	$text_engine_init = curl_init("text_engine");

	$data = json_encode([
		'operation' => 'get_uid',
		'to_dialog' => $dialog
	]);

	curl_setopt($text_engine_init, CURLOPT_POSTFIELDS,     $data);
	curl_setopt($text_engine_init, CURLOPT_POST,           1);
	curl_setopt($text_engine_init, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));
	curl_setopt($text_engine_init, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($text_engine_init);
	curl_close($text_engine_init);

	return intval($result);
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
	$res = DataBaseManager::getConnection()->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1;");
	$res->execute([intval($user_id), intval($bot_id)]);
	$data = $res->fetch(PDO::FETCH_ASSOC)['state'];
	if ($data === NULL)
	{
		DataBaseManager::getConnection()->prepare("INSERT INTO users.bot_relations (user_id, bot_id, state) VALUES (?, ?, -1);")->execute([intval($user_id), intval($bot_id)]);
	}

	if ($allow)
	{
		return DataBaseManager::getConnection()->prepare("UPDATE users.bot_relations SET state = 1 WHERE user_id = ? AND bot_id = ? LIMIT 1;")->execute([intval($user_id), intval($bot_id)]);
	} else
	{
		return DataBaseManager::getConnection()->prepare("UPDATE users.bot_relations SET state = -1 WHERE user_id = ? AND bot_id = ? LIMIT 1;")->execute([intval($user_id), intval($bot_id)]);
	}
}

/**
 * Checks the messaging write allowance
 * return true if allow or false if not
 *
 * Parameters:
 * @param $user_id - user id who state must be checked
 * @param $bot_id - bot id for who must check
*/
function is_chat_allowed ($connection, $user_id, $bot_id)
{
	// getting state
	$res = DataBaseManager::getConnection()->prepare("SELECT state FROM users.bot_relations WHERE user_id = ? AND bot_id = ? LIMIT 1;");
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
