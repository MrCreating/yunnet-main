<?php

/**
 * Connect to LP API
*/

// default params
$params = [
	'mode' => strtolower($_REQUEST["mode"]) === "sse" ? "sse" : "polling"
];

if ($only_params)
	return $params;

if (!in_array('3', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

// getting LP data
$lp_data = get_polling_data(get_cache(), $context["user_id"], $params['mode']);

die(json_encode(
	['response' => $lp_data]
));

?>