<?php

require_once __DIR__ . "/../../bin/functions/wall.php";

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_posts':
			$posts = get_news($connection, Context::get()->getCurrentUser()->getId(), Context::get()->getCurrentUser()->getId());

			die(json_encode($posts));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}
?>