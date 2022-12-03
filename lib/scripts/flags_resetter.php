<?php

error_reporting(0);
echo "[+] Including API..." . PHP_EOL;

require __DIR__ . '/bin/base_functions.php';
require __DIR__ . '/bin/functions/messages.php';

$maxUserId = 50;
$connection = DataBaseManager::getConnection();

for ($user_id = 1; $user_id <= $maxUserId; $user_id++)
{
	echo "[+] Working with user " . $user_id . PHP_EOL;

	for ($page = 1; $page < 20; $page++)
	{
		$offsetOfPage = ($page-1)*30;

		$res = DataBaseManager::getConnection()->prepare("SELECT DISTINCT uid, last_time FROM messages.members_chat_list WHERE hidden = 0 AND user_id = ? AND lid != 0".($only_chats ? ' AND uid < 0' : '')." ORDER BY last_time DESC LIMIT ".intval($offsetOfPage).", 30;");

		$res->execute([intval($user_id)]);
		$chats = $res->fetchAll(PDO::FETCH_ASSOC);

		$uids = [];
		foreach ($chats as $key => $value) 
		{
			if (!in_array(intval($value['uid']), $uids))
			{
				$uids[] = intval($value['uid']);
			}
		}

		foreach ($uids as $index => $uid) 
		{
			$chat = get_chat_data_by_uid($connection, $uid, $user_id);

			if (!$chat["last_message"])
			{
				echo "Chat uid " . $uid . " is not exists on user " . $user_id . "! Hiding it..." . PHP_EOL;

				DataBaseManager::getConnection()->prepare("UPDATE messages.members_chat_list SET hidden = 1 WHERE uid = ? AND user_id = ?;")->execute([$uid, $user_id]);
			}
		}
	}
}

echo "[+] All done." . PHP_EOL;

?>