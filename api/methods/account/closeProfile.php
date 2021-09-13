<?php

/**
 * API for close or open profile
*/

if ($only_params)
	return $params;

if (!in_array('4', $context['permissions']))
	die(create_json_error(-1, "Authentication failed: this access key don't have permission to call this method"));

if ($context['owner_object']->getType() === "bot")
	die(create_json_error(-10, 'This method is not available from bot account'));

$settings = $context['owner_object']->getSettings()->getValues();
$settings->closed_profile = !$settings->closed_profile;

$user_id = intval($context['user_id']);
$new_settings = json_encode($settings);

$res = $connection->prepare("UPDATE users.info SET settings = :settings WHERE id = :id LIMIT 1;");
$res->bindParam(":settings", $new_settings, PDO::PARAM_STR);
$res->bindParam(":id",       $user_id,      PDO::PARAM_INT);
$res->execute();

die(json_encode(array('response'=>intval($settings->closed_profile))));
?>