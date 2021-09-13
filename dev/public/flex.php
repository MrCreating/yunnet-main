<?php

/**
 * Original flex php symlink
*/

// non-die param
$dev = true;

require __DIR__ . "/../../pages/public/flex.php";

/**
 * Getting current bots list.
 * Max 30 bots per account
*/
if ($action === 'get_bots_list')
{
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));

	if (!function_exists('get_bots_list'))
		require __DIR__ . '/../../bin/functions/bots.php';

	$result = get_bots_list($connection, $context->getCurrentUser()->getId(), true);

	// ok!
	die(json_encode($result));
}

/**
 * Getting current apps list.
*/
if ($action === 'get_apps_list')
{
	if (!$context->isLogged()) die(json_encode(array('unauth'=>1)));

	if (!function_exists('get_apps_list'))
		require __DIR__ . '/../../bin/functions/auth.php';

	$apps_list = get_apps_list($connection, $context->getCurrentUser()->getId(), intval($_POST['offset']), intval($_POST['count']));

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
	$value = strval($_POST['value']);

	if ($value === '*')
		die(json_encode(array($currentLanguage)));

	$resultedString = $currentLanguage[$value];
	if (!$resultedString)
		die(json_encode(array('error'=>1)));

	die(json_encode(array('value'=>$resultedString)));
}

die(json_encode(array('flex' => 1)));
?>