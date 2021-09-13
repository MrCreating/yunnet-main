<?php

/**
 * Upload handler
*/

// default params
$params = [
	'query' => $_REQUEST["query"]
];

if ($only_params)
	return $params;

if (!in_array('3', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if (!function_exists('get_upload_link'))
	require __DIR__ . "/../../../bin/functions/uploads.php";

// type is required
if (!$params["query"])
	die(create_json_error(15, 'Some parameters was missing or invalid: query is required'));

// fetching upload
$result = fetch_upload($connection, $params["query"], $context["user_id"]);
if (!$result)
	die(create_json_error(126, 'Upload error'));

// sending result.
die(json_encode($result->toArray()));
?>