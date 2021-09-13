<?php

/**
 * Getting upload file query
*/

// default params
$params = [
	'type' => $_REQUEST["type"]
];

if ($only_params)
	return $params;

if (!in_array('3', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if (!function_exists('get_upload_link'))
	require __DIR__ . "/../../../bin/functions/uploads.php";

// type is required
if (!$params["type"])
	die(create_json_error(15, 'Some parameters was missing or invalid: type is required'));

// getting upload data
$data = get_upload_link($connection, $context["user_id"], 'https://yunnet.ru', $params["type"]);
if (!$data)
	die(create_json_error(125, 'Attachment type is invalid'));

// sending result
die(json_encode([
	'response' => [
		'owner_id' => $data["owner_id"],
		'query'    => $data["query"]
	]
]));
?>