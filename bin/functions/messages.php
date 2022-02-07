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
 * Gets last uid of all yunnet dialogs.
 * @return last free uid.
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