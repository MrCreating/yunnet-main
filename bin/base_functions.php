<?php

namespace unt\functions;

use unt\objects\Context;
use unt\objects\Project;
use unt\platform\Cache;
use unt\platform\DataBaseManager;
use PDO;

/**
 * Initial and most used functions and operations
 */

// returns a page origin for CORS.
function get_page_origin () 
{
	$link_without_params = (Project::isProduction() ? 'https://' : 'http://') . explode('/', explode('?', $_SERVER['HTTP_REFERER'])[0])[2];
	return substr($link_without_params, 0, strlen($link_without_params));
}

// explode string by length
function explode_length ($text, $length): array
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
		$res = DataBaseManager::getConnection()->prepare("SELECT settings FROM users.info WHERE id = ?;");
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
		$res = DataBaseManager::getConnection()->prepare("SELECT id FROM users.info WHERE screen_name = ? LIMIT 1;");
		$res->execute([$name]);

		$id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);
		if ($id === 0)
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT id FROM bots.info WHERE screen_name = ? LIMIT 1;");
			$res->execute([$name]);

			$id = intval($res->fetch(PDO::FETCH_ASSOC)['id'])*-1;
		}
	}
	else
	{
		$res = DataBaseManager::getConnection()->prepare("SELECT id FROM users.info WHERE id = ? LIMIT 1;");
		$res->execute([strval($user_id)]);

		$id = intval($res->fetch(PDO::FETCH_ASSOC)['id']);
		if ($id === 0)
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT id FROM bots.info WHERE id = ? LIMIT 1;");
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
function is_empty ($text): bool
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
function capitalize ($str, $encoding = "UTF-8"): string
{
	$str = mb_ereg_replace('^[\ ]+', '', $str);

    return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).mb_substr($str, 1, mb_strlen($str), $encoding);
}

// update online time
function update_online_time ($connection, $old_time, $user_id): bool
{
	$old_time = $old_time > 0 ? intval($old_time) : 0;

	if (((time() - $old_time) >= 0) || $old_time <= 0)
		return DataBaseManager::getConnection()->prepare("UPDATE users.info SET is_online = ? WHERE id = ? LIMIT 1;")->execute([time()+30, intval($user_id)]);

	return false;
}