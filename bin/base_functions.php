<?php

/**
 * Initial and most used functions and operations
 */

// connect the default modules
require_once __DIR__ . '/objects/request.php';
require_once __DIR__ . '/objects/context.php';
require_once __DIR__ . '/objects/letter.php';
require_once __DIR__ . '/platform-tools/cache.php';
require_once __DIR__ . '/platform-tools/database.php';
require_once __DIR__ . '/platform-tools/data.php';
require_once __DIR__ . '/platform-tools/event_manager.php';
require_once __DIR__ . '/objects/user.php';
require_once __DIR__ . '/objects/bot.php';
require_once __DIR__ . '/parsers/attachments.php';

// returns a page origin for CORS.
function get_page_origin () 
{
	$link_without_params = (getenv('UNT_PRODUCTION') === '1' ? 'https://' : 'http://') . explode('/', explode('?', $_SERVER['HTTP_REFERER'])[0])[2];
	return substr($link_without_params, 0, strlen($link_without_params));
}

// explode string by length
function explode_length ($text, $length)
{
	$result = [];

	$currentLength = 0;
	$partsCount = intval(strlen($text) / $length);

	for ($i = 0; $i < $partsCount; $i++)
	{
		$result[] = substr($text, $currentLength, $length);

		$currentLength += $length;
	}

	return $result;
}

// get language function
function get_language ($connection, $current_user = NULL)
{
	return Context::get()->getLanguage();
}

// get rules text
function get_rules_text ()
{
	$lang = Context::get()->getLanguage()->id;

	return file_get_contents(__DIR__ . '/languages/policy/' . $lang . '/rules');
}

// get terms text
function get_terms_text ()
{
	$lang = Context::get()->getLanguage()->id;

	return file_get_contents(__DIR__ . '/languages/policy/' . $lang . '/terms');
}

// get language function
function get_dev_language ($connection)
{
	$language_code = "en";

	if ( !isset($_SESSION['user_id']) )
	{
		if ( isset($_SESSION['lang']) )
		{
			$language_code = strtolower( $_SESSION['lang'] );
		}
		else
		{
			// get language by header
			$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			switch ( $lang )
			{
				case 'ru':
					$language_code = "ru";
					break;
				default:
					break;
			}
		}
	}
	else
	{
		$res = $connection->prepare("SELECT settings FROM users.info WHERE id = ?;");
		$res->execute([strval($_SESSION['user_id'])]);

		$settings = json_decode($res->fetch(PDO::FETCH_ASSOC)['settings'], true);
		if ($settings['lang'] === 'ru')
			$language_code = 'ru';
	}

	$cache = Cache::getCacheServer();
	$lang = json_decode($cache->get( 'lang_dev_' . $language_code ), true);

	if ( !$lang )
	{
		$language_json = file_get_contents(__DIR__.'/languages/dev/' . $language_code);

		$cache->set( 'lang_dev_' . $language_code, $language_json );
		$lang = json_decode($language_json, true);
	}

	return $lang;
}

// get DB connection class
function get_database_connection ()
{
	return DataBaseManager::getConnection();
}

// returns user or bot id by screen name
function resolve_id_by_name ($connection, $name)
{
	$name_wk = strtolower(explode("/", $name)[1]);
	if ($name_wk === "")
		$name = strtolower($name);
	else
		$name = $name_wk;
	$user_id = 0;

	if (substr($name, 0, 2) === "id")
	{
		$user_id    = intval(substr($name, 2));
		$went_by_id = true;
	}
	if (substr($name, 0, 3) === "bot")
	{
		$user_id    = intval(substr($name, 3))*-1;
		$went_by_id = true;
	}

	if ($user_id === 0)
	{
		$res = $connection->prepare("SELECT id FROM users.info WHERE screen_name = ? LIMIT 1;");
		$res->execute([$name]);

		$id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);
		if ($id === 0)
		{
			$res = $connection->prepare("SELECT id FROM bots.info WHERE screen_name = ? LIMIT 1;");
			$res->execute([$name]);

			$id = intval($res->fetch(PDO::FETCH_ASSOC)['id'])*-1;
		}
	}
	else
	{
		$res = $connection->prepare("SELECT id FROM users.info WHERE id = ? LIMIT 1;");
		$res->execute([strval($user_id)]);

		$id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);
		if ($id === 0)
		{
			$res = $connection->prepare("SELECT id FROM bots.info WHERE id = ? LIMIT 1;");
			$res->execute([strval($user_id*-1)]);

			$id = intval($res->fetch(PDO::FETCH_ASSOC)['id'])*-1;
		}
	}

	if ($id === 0)
	{
		$result = false;
	}
	else
	{
		$type = "user";
		if ($id < 0)
			$type = "bot";

		$result = [
			'id'           => $id,
			'went_by_id'   => $went_by_id,
			'account_type' => $type
		];
	}

	return $result;
}

// checks of sring empty
function is_empty ($text)
{
	$test = implode('', explode(PHP_EOL, $text));
	if ($test == '') {
		return true;
	}
	$test = implode('', explode('\n', $test));
	if ($test == '') {
		return true;
	}
			
	$test = implode('', explode(' ', $test));
	if ($test == '') {
		return true;
	}
		
	return false;
}

// capitalize the string
function capitalize ($str, $encoding = "UTF-8")
{
	$str = mb_ereg_replace('^[\ ]+', '', $str);

    return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str), $encoding);
}

// checks if user exists
function user_exists ($connection, $email)
{
	return Entity::findById(intval($email)) !== NULL;
}

// get chat query
function get_chat_query ($uid, $leaved_time, $return_time, $is_leaved, $is_kicked, $user_id, $last_message = true, $cleared_message_id = 0, $offset = 0, $count = 100)
{
	if ($last_message)
		$last_message = ' DESC LIMIT 1';
	else
		$last_message = ' DESC LIMIT '.intval($offset).', '.intval($count);

	$query = "SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > ".$cleared_message_id." AND (deleted_for NOT LIKE '%".intval($user_id).",%' OR deleted_for IS NULL) AND uid = ".$uid." ORDER BY local_chat_id".$last_message.";";

	if ($uid < 0)
	{
		if ($is_kicked || $is_leaved)
		{
			$query = 'SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND uid = '.$uid.' AND time <= '.$leaved_time.' ORDER BY local_chat_id'.$last_message.';';
		} else {
			if (!$is_leaved && $return_time !== 0) 
			{
				$query = 'SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard FROM messages.chat_engine_1 WHERE deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND uid = '.$uid.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND (time <= '.$leaved_time.' OR time >= '.$return_time.') ORDER BY local_chat_id'.$last_message.';';
							
				if ($leaved_time === 0)
				{
					$query = 'SELECT local_chat_id, is_edited, time, text, event, new_src, new_title, owner_id, to_id, reply, attachments, keyboard FROM messages.chat_engine_1 WHERE (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) AND deleted_for_all != 1 AND local_chat_id > '.$cleared_message_id.' AND uid = '.$uid.' OR uid = '.$uid.' AND time >= '.$return_time.' AND (deleted_for NOT LIKE "%'.intval($user_id).',%" OR deleted_for IS NULL) ORDER BY local_chat_id'.$last_message.';';
				}
			}
		}
	}

	return $query;
}

function get_polling_data ($cache, $user_id, $mode = "sse")
{
	$done = openssl_encrypt(strval($user_id.'_'.strval(rand(1, 1000000000)).'_permissions'), 'AES-256-OFB', strval(rand(1, 10000000000)), 0, strval(rand(1, 1000000000)), rand(1, 9999999));

	$cache->set($done, intval($user_id));
	$result = array('url'=> Project::getDefaultDomain() . ':8080?mode=listen&state='.$mode.'&key='.urlencode($done), 'last_event_id' => 0, 'owner_id' => intval($user_id));

	return $result;
}

function get_user_timezone ($connection, $user_id)
{
	$res = $connection->prepare("SELECT timezone FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([$user_id]);

	$timezone = $res->fetch(PDO::FETCH_ASSOC)["timezone"];
	if (!$timezone)
		$timezone = "Europe/Moscow";

	return $timezone;
}

function get_timezones_list ($connection)
{
	$res = $connection->prepare("SELECT worldtime, phptime FROM utils.timestamps;");
	$res->execute();

	return $res->fetchAll(PDO::FETCH_ASSOC);
}

// checks  the user_id's password. Returns boolean
function verify_user_password ($connection, $user_id, $password)
{
	if (preg_match("/[^a-zа-яёЁбБвВгГдДжЖзЗиИйЙкКлЛмМнНоОпПРрсСтТуУфФхХцЦчЧшШщЩъЪыЫьЬэЭюЮяЯРА-ЯA-Z-'*@#$%_.\d!@#$%\^&*]/", $password)  || is_empty($password) || strlen($password) < 6)
		return false;

	$res = $connection->prepare("SELECT password FROM users.info WHERE id = ?");
	$res->execute([$user_id]);
	$old_password_hash = strval($res->fetch(PDO::FETCH_ASSOC)["password"]);
	
	return password_verify($password, $old_password_hash);
}

// converts integers like 1000000 to 1М. Returns converted string
function nice_string ($number)
{
	$number = intval($number);
	$count  = strval($number);

	if ($number >= 1000)
		$count = intval($number/1000) . "К";
	if ($number >= 1000000)
		$count = intval($number/1000000) . "М";

	return $count;
}

// update online time
function update_online_time ($connection, $old_time, $user_id)
{
	$old_time = $old_time > 0 ? intval($old_time) : 0;

	if (((time() - $old_time) >= 0) || $old_time <= 0)
		return $connection->prepare("UPDATE users.info SET is_online = ? WHERE id = ? LIMIT 1;")->execute([time()+30, intval($user_id)]);

	return false;
}
?>