<?php
error_reporting(0);

require __DIR__ . '/../../bin/base_functions.php';

$connection = DataBaseManager::getConnection();

for ($i = 1; $i <= 100; $i++)
{
	$user = new User($i);
	if ($user->valid())
	{
		echo "[+] Working with user " . $user->getId() . PHP_EOL;

		$settings = $user->getSettings()->toArray();
		
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_account_language = ? WHERE id = ? LIMIT 1;")->execute([$settings['account']['language'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_account_is_closed = ? WHERE id = ? LIMIT 1;")->execute([$settings['account']['is_closed'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_privacy_can_write_messages = ? WHERE id = ? LIMIT 1;")->execute([$settings['privacy']['can_write_messages'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_privacy_can_write_on_wall = ? WHERE id = ? LIMIT 1;")->execute([$settings['privacy']['can_write_on_wall'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_privacy_can_comment_posts = ? WHERE id = ? LIMIT 1;")->execute([$settings['privacy']['can_comment_posts'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_privacy_can_invite_to_chats = ? WHERE id = ? LIMIT 1;")->execute([$settings['privacy']['can_invite_to_chats'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_push_notifications = ? WHERE id = ? LIMIT 1;")->execute([$settings['push']['notifications'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_push_sound = ? WHERE id = ? LIMIT 1;")->execute([$settings['push']['sound'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_theming_js_allowed = ? WHERE id = ? LIMIT 1;")->execute([$settings['theming']['js_allowed'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_theming_new_design = ? WHERE id = ? LIMIT 1;")->execute([$settings['theming']['new_design'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_theming_current_theme = ? WHERE id = ? LIMIT 1;")->execute([$settings['theming']['current_theme'], $user->getId()]);
		DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_theming_menu_items = ? WHERE id = ? LIMIT 1;")->execute([implode(',', $settings['theming']['menu_items']), $user->getId()]);
	}
}

?>