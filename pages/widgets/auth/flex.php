<?php

/**
 * Auth Flex API.
*/

$dev = true;

require_once __DIR__ . '/../../../bin/objects/app.php';
require_once __DIR__ . '/../../../bin/functions/auth.php';
require_once __DIR__ . '/../../public/flex.php';

/**
 * Get apps by ID.
*/
if ($action === 'get_app_by_id')
{
	$app_id = intval($_POST['app_id']);

	$app = new App($app_id);
	if ($app->valid())
	{
		die(json_encode($app->toArray()));
	}

	die(json_encode(array('error' => 1)));
}
if ($action === 'resolve_auth')
{
	header('Access-Control-Allow-Origin: ' . get_page_origin());
	header('Access-Control-Allow-Credentials: true');

	if (!Context::get()->allowToUseUnt())
		die(json_decode(array('error' => 1)));

	$app_id   = intval($_POST['app_id']);
	$owner_id = Context::get()->getCurrentUser()->getId();

	$perms    = explode(',', strval($_POST['permissions']));
	$permissions = [];
	foreach ($perms as $index => $id)
	{
		if (intval($id) < 1 || intval($id) > 4)
			continue;

		$permissions[] = strval($id);
	}

	$result = create_token($connection, $owner_id, $app_id, $permissions);

	if (!$result)
		die(json_decode(array('error' => 1)));

	die(json_encode($result));
}

?>