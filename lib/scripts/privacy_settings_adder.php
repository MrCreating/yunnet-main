<?php
require __DIR__ . "/bin/base_functions.php";
$connection = DataBaseManager::getConnection();

$res = DataBaseManager::getConnection()->prepare("SELECT id, settings FROM users.info;");
$res->execute();

$default = [
				'lang'    => 'ru',
				'privacy' => [
					'can_write_on_wall' => 0,
					'can_write_messages' => 0,
					'can_invite_to_chats' => 0,
					'can_comment_posts' => 0
				],
				'notifications' => [
					'sound' => 1,
					'notifications' => 0
				],
				'closed_profile' => 0
			];

$data = $res->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as $key => $value) {
	$settings = json_decode($value["settings"], true);
	$user_id  = intval($value["id"]);
	if (!$settings)
		$settings = $default;

	$settings["privacy"]["can_comment_posts"] = 0;
	$settings["closed_profile"] = 0;

	$res = DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings = :settings WHERE id = :id;");
	$res->bindParam(":settings", json_encode($settings), PDO::PARAM_STR);
	$res->bindParam(":id",       intval($user_id),       PDO::PARAM_INT);
	if ($res->execute())
		echo '['.$user_id.'] Updated!'.PHP_EOL;
}
?>