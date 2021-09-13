<?php

/**
 * news get php
*/

if ($only_params)
	return $params;

// bots can not use this method
if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

// connecting modules
if (!function_exists('get_news'))
	require __DIR__ . '/../../../bin/functions/wall.php';

// news array
$news = get_news($connection, $context['user_id']);

die(json_encode(array(
	'items' => $news,
	'count' => count($news)
)));
?>