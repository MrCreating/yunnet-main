<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . '/../bin/objects/api.php';
require_once __DIR__ . '/../bin/objects/AbstractAPIMethod.php';

$api = API::get();//->setContentType('application/json');

$method = AbstractAPIMethod::findMethod($api, $api->getRequestParams());
if (!$method)
    $api->sendError(-5, "Method not found");

if (!$api->valid() && !$method->isPublicMethod())
	$api->sendError(-1, 'Authentication failed: access key is invalid');
if (!$method->isPublicMethod() && $api->getOwner()->isBanned())
	$api->sendError(-30, 'Authentication failed: account is banned');

$api->callMethod($method, function (?APIResponse $result, ?APIException $error) {
    if ($error)
        $error->send();

	die(var_dump($result));
});

$api->sendError(-2, 'API is temporally unavailable');
