<?php

/**
 * Functions for chat links.
*/

/**
 * Get the chat info by link
 * @return Chat instance
 *
 * Parameters:
 * @param $query - chat link query
 * @param $user_id - current user id.
*/
function get_chat_by_query ($connection, $query, $user_id)
{
	// connecting modules
	if (!class_exists('Chat'))
		require __DIR__ . '/../objects/chats.php';

	// getting info.
	$res = $connection->prepare("SELECT uid FROM messages.members_engine_1 WHERE link = :link LIMIT 1;");

	// bind params and execute
	$res->bindParam(":link", $query, PDO::PARAM_STR);
	if ($res->execute())
	{
		$data = $res->fetch(PDO::FETCH_ASSOC);
		if ($data['uid'])
		{
			return new Chat($connection, intval($data['uid']));
		}
	}

	// another error
	return false;
}

?>