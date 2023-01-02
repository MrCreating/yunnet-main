<?php

/**
 * Auth Flex API.
*/

use unt\objects\App;
use unt\objects\Context;
use unt\objects\Project;
use unt\objects\Request;

$dev = true;

require_once __DIR__ . '/../../../bin/objects/App.php';
require_once __DIR__ . '/../../../unt/public/flex.php';

/**
 * Get apps by ID.
*/
if ($action === 'get_app_by_id')
{
	$app_id = intval(Request::get()->data['app_id']);

	$app = new App($app_id);
	if ($app->valid())
	{
		die(json_encode($app->toArray()));
	}

	die(json_encode(array('error' => 1)));
}
if ($action === 'resolve_auth')
{
	header('Access-Control-Allow-Origin: ' . Project::getOrigin());
	header('Access-Control-Allow-Credentials: true');

	if (!Context::get()->allowToUseUnt())
		die(json_encode(array('error' => 1)));

	$app_id   = intval(Request::get()->data['app_id']);
	$owner_id = Context::get()->getCurrentUser()->getId();

	$app = new App($app_id);

	if (!($token = $app->createToken(explode(',', strval(Request::get()->data['permissions'])))))
		die(json_encode(array('error' => 1)));

	die(json_encode($token->toArray()));
}

?>