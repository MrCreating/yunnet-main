<?php

/**
 * API for auth call (direct auth must be ENABLED)
*/

$params = [
	'app_id'   => isset($_POST['app_id']) ? intval($_POST['app_id']) : intval($_REQUEST['app_id']),
	'login'    => isset($_POST['login']) ? strval($_POST['login']) : strval($_REQUEST['login']),
	'password' => isset($_POST['password']) ? strval($_POST['password']) : strval($_REQUEST['password'])
];

if ($only_params)
	return $params;

if (!$params["app_id"])
	die(create_json_error(15, 'Some parameters was missing or invalid: app_id is required'));

if (!$params["login"])
	die(create_json_error(15, 'Some parameters was missing or invalid: login is required'));

if (!$params["password"])
	die(create_json_error(15, 'Some parameters was missing or invalid: password is required'));

if (!function_exists('auth_user'))
	require __DIR__ . '/../../../bin/functions/auth.php';
if (!class_exists('App'))
	require __DIR__ . '/../../../bin/objects/app.php';
if (!class_exists('Entity'))
	require __DIR__ . '/../../../bin/objects/entities.php';

// getting an app instance
$app = new App($params['app_id']);

if (!$app->valid())
	die(create_json_error(-24, 'App not found'));

if (!$app->isDirectAuthAllowed())
	die(create_json_error(-25, 'App does not allowed for direct auth'));

if (is_empty($params['login']))
	die(create_json_error(15, 'Some parameters was missing or invalid: login is empty'));
	
if (is_empty($params['password']))
	die(create_json_error(15, 'Some parameters was missing or invalid: password is empty'));

$result = auth_user($connection, $params['login'], $params['password']);
if (!$result)
	die(create_json_error(-27, 'Login or password incorrect'));

$new_token = create_token($connection, $result['user_id'], $app->getId());
if (!$new_token)
	die(create_json_error(-30, 'Unknown auth error'));

die(json_encode(array(
	'response' => [
		'user_id' => intval($result['user_id']),
		'token'   => $new_token['token']
	]
)));
?>