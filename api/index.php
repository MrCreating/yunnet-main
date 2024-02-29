<?php

$api = API::get()->setContentType('application/json');
if (!$api->valid())
	$api->sendError(-1, 'Authentication failed: access key is invalid');
if ($api->getOwner()->isBanned())
	$api->sendError(-30, 'Authentication failed: account is banned');

$api->callMethod($api->getRequestedMethod(), $api->getRequestParams(), function (?APIResponse $result, ?APIException $error) {
	if ($error !== null)
		return $error->send();

	if ($result !== null)
		return $result->send();

	return $api->sendError(-10, 'Internal server error');
});

$api->sendError(-2, 'API is temporally unavailable');
?>
