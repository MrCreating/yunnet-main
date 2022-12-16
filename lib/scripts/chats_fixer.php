<?php
ini_set("display_errors", 0);
error_reporting(0);

require __DIR__ . '/bin/base_functions.php';

$connection = DataBaseManager::getConnection();

$res = DataBaseManager::getConnection()->prepare("SELECT id FROM users.info;");
$res->execute();

$user_ids = $res->fetchAll(PDO::FETCH_ASSOC);
foreach ($user_ids as $key => $value) {
	$user_id = intval($value['id']);
	$chats_l = get_chats($connection, $user_id, 50, 100);

	foreach ($chats_l as $index => $chat) {
		$uid  = intval($chat['uid']);
		$time = intval($chat['last_message']['time']);

		$res = DataBaseManager::getConnection()->prepare("UPDATE messages.members_chat_list SET last_time = ? WHERE uid = ? AND last_time = 0")->execute([intval($time), intval($uid)]);
		
		var_dump($res).PHP_EOL;
	}
}
?>