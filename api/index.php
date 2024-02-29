<?php

use unt\exceptions\APIException;
use unt\objects\API;
use unt\objects\APIResponse;

$api = API::get()->setContentType('application/json');
if (!$api->valid())
	$api->sendError(-1, 'Authentication failed: access key is invalid');
if ($api->getOwner()->isBanned())
	$api->sendError(-30, 'Authentication failed: account is banned');

$api->callMethod($api->getRequestedMethod(), $api->getRequestParams(), function (?APIResponse $result, ?APIException $error) use ($api) {
	if ($error !== null)
		$error->send();

	if ($result !== null)
		$result->send();

	$api->sendError(-10, 'Internal server error');
});

$api->sendError(-2, 'API is temporally unavailable');
?>
