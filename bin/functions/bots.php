<?php
/**
 * File with bot functions
*/
if (!class_exists('Bot'))
	require __DIR__ . "/../objects/entities.php";

/**
 * Receives a bots list of $user_id
 * @return array of Bot instances
*/
function get_bots_list ($connection, $user_id, $doneObjects = false)
{
	$result = [];

	$res    = $connection->prepare("SELECT DISTINCT id FROM bots.info WHERE owner_id = ? AND is_deleted = 0 LIMIT 30;");
	$res->execute([intval($user_id)]);

	$data = $res->fetchAll(PDO::FETCH_ASSOC);
	foreach ($data as $index => $bot_data) {
		$bot = new Bot(intval($bot_data["id"]));
		if ($bot->valid())
		{
			if ($doneObjects) 
			{
				$doneBotArray = $bot->toArray();

				$doneBotArray['privacy'] = [
					'can_write_messages'  => $bot->getSettings()->getValues()->privacy->can_write_messages,
					'can_write_on_wall'   => $bot->getSettings()->getValues()->privacy->can_write_on_wall,
					'can_invite_to_chats' => $bot->getSettings()->getValues()->privacy->can_invite_to_chats
				];

				$result[] = $doneBotArray;
			}

			else $result[] = $bot;
		}
	}

	return $result;
}

/**
 * Creates a new bot.
 * @return true if bot created and false if error.
 *
 * Parameters:
 * @param $owner_id - user_id which will be owner of new bot.
 * @param $bot_name - title of new bot (64-length max)
*/
function create_bot ($connection, $owner_id, $bot_name)
{
	// max 30 bots per account
	if (count(get_bots_list($connection, $owner_id)) >= 30) return false;

	$bot_name = trim($bot_name);

	// checking
	if (is_empty($bot_name) || strlen($bot_name) > 64) return false;

	// only allowed letters and digits, space, and some symbols.
	if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-@$*#!%\d ]/ui", $bot_name)) return false;

	// default settings.
	$settings = [
		"privacy" => [
			"can_write_messages"  => 0,
			"can_write_on_wall"   => 2,
			"can_invite_to_chats" => 1,
			"can_comment_posts"   => 0
		]
	];

	// creating...
	$res = $connection->prepare("INSERT INTO bots.info (name, owner_id, creation_time, settings) VALUES (:name, :owner_id, :cr_time, :settings);");

	$new_time         = time();
	$encoded_settings = json_encode($settings);

	$res->bindParam(":name",     $bot_name,         PDO::PARAM_STR);
	$res->bindParam(":owner_id", $owner_id,         PDO::PARAM_INT);
	$res->bindParam(":cr_time",  $new_time,         PDO::PARAM_INT);
	$res->bindParam(":settings", $encoded_settings, PDO::PARAM_STR);

	// return creation result.
	return $res->execute();
}

/** 
 * Updates bot photo
 * @return true if success, or false if error.
 */
function update_bot_photo ($connection, int $bot_id, Photo $photo)
{
	// checking Attachment instance
	if (!$photo || !$photo->valid()) return false;

	$query = $photo->getQuery();
	$res = $connection->prepare("UPDATE bots.info SET photo_path = :query WHERE id = :id AND is_deleted = 0 LIMIT 1;");

	$res->bindParam(":query", $query,  PDO::PARAM_STR);
	$res->bindParam(":id",    $bot_id, PDO::PARAM_INT);

	// return update result.
	return $res->execute();
}

/**
 * Updates bot name
 * @return true if success or false if error
 *
 * Parameters:
 * @param $bot_id - bot_id for name update.
 * @param $new_name - new bot name. Rules for name is similar for creation.
*/
function update_bot_name ($connection, int $bot_id, string $new_name)
{
	$new_name = trim($new_name);

	// checking name for empty and long-length
	if (is_empty($new_name) || strlen($new_name) > 64)
		return false;

	// only allowed letters, digits, space and some symbols.
	if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-@$*#!%\d ]/ui", $new_name))
		return false;

	// updating...
	$res = $connection->prepare("UPDATE bots.info SET name = :new_name WHERE id = :id AND is_deleted = 0 LIMIT 1;");
	$res->bindParam(":new_name", $new_name, PDO::PARAM_STR);
	$res->bindParam(":id",       $bot_id,   PDO::PARAM_INT);

	// return result of update.
	return $res->execute();
}

?>