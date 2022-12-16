<?php

use unt\objects\Context;
use unt\objects\Request;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	switch ($action) {
		case 'get_posts':
			$posts = Context::get()->getCurrentUser()->getNewsList();
			die(json_encode($posts));
		break;
		
		default:
		break;
	}

	die(json_encode(array('error' => 1)));
}
?>