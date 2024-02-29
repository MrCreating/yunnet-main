<?php

/**
 * Original flex php symlink
*/

// non-die param
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Request;

$dev = true;

require __DIR__ . "/../../unt/public/flex.php";

/**
 * @var string $action
 */

/**
 * Getting current bots list.
 * Max 30 bots per account
*/
if ($action === 'get_bots_list')
{
	if (!Context::get()->isLogged()) die(json_encode(array('unauth'=>1)));

	$result = Bot::getList();

	// ok!
	die(json_encode(array_map(function (Bot $bot) {
        return $bot->toArray();
    }, $result)));
}

/**
 * Getting current apps list.
*/
if ($action === 'get_apps_list')
{
	if (!Context::get()->isLogged()) die(json_encode(array('unauth'=>1)));

    $offset = intval(Request::get()->data['offset']);
    $count = intval(Request::get()->data['count']);

	$apps_list = get_apps_list($connection, Context::get()->getCurrentUser()->getId(), intval(Request::get()->data['offset']), intval(Request::get()->data['count']));

	$result = [];
	foreach ($apps_list as $index => $app) {
		$result[] = $app->toArray();
	}

	die(json_encode($result));
}

/**
 * Get dev language value
*/
if ($action === 'get_dev_lang_value')
{
	$currentLanguage = get_dev_language($connection);
	$value = strval(Request::get()->data['value']);

	if ($value === '*')
		die(json_encode(array($currentLanguage)));

	$resultedString = $currentLanguage[$value];
	if (!$resultedString)
		die(json_encode(array('error'=>1)));

	die(json_encode(array('value'=>$resultedString)));
}

die(json_encode(array('flex' => 1)));
?>
