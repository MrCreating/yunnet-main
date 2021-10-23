<?php

/**
 * Here will be setting actual for bots and users.
*/

// update short link. Returns true if success
// returns -1 if name is already in use
// returns false if name is incorrect
function update_screen_name ($connection, $user_id, $new_value)
{
	/*
	// converting all data provided to int
	$user_id     = intval($user_id);
	$selected_id = intval($user_id > 0 ? $user_id : $user_id*-1);

	// if new_value is null - delete current screen_name.
	if (!$new_value)
	{
		$res = $connection->prepare($user_id > 0 ? "UPDATE users.info SET screen_name = NULL WHERE id = :user_id LIMIT 1;" : "UPDATE bots.info SET screen_name = NULL WHERE id = :user_id LIMIT 1;");
		$res->bindParam(":user_id", $selected_id, PDO::PARAM_INT);

		return $res->execute();
	}

	// prepare new value for set.
	$new_value = explode(' ', strtolower($new_value))[0];
	if (is_empty($new_value) || strlen($new_value) > 64)
		return false;

	// screen_name can contain only leters, _, and digits.
	if (!preg_match("/^[a-z]{1}[a-z_\d\s]*[a-z_\s\d]{1}$/i", $new_value))
		return false;

	// if screen name is free - continue
	if (is_screen_used($connection, $new_value))
		return -1;

	// updating and return execute result.
	$res = $connection->prepare($user_id > 0 ? "UPDATE users.info SET screen_name = :new_name WHERE id = :user_id LIMIT 1;" : "UPDATE bots.info SET screen_name = :new_name WHERE id = :user_id LIMIT 1;");

	$res->bindParam(":new_name", $new_value,   PDO::PARAM_STR);
	$res->bindParam(":user_id",  $selected_id, PDO::PARAM_INT);

	return $res->execute();
	*/

	$editor = context()->getCurrentUser()->edit();
	if ($editor)
	{
		$result = $editor->setScreenName($new_value);
		if ($result)
			return $editor->apply();
	}

	return false;
}

// check if screen_name is already in use.
function is_screen_used ($connection, $screen_name)
{
	// connecting module if needed.
	/*if (!function_exists('get_default_pages'))
		require __DIR__ . "/../base_functions.php";

	// get all pages.
	$pages = get_default_pages();
	if (in_array('/'.strtolower($screen_name), $pages))
		return true;

	// links like idXXXXX is not allowed
	if (substr($screen_name, 0, 2) === "id")
	{
		return true;
	}

	// links like idXXXXX is not allowed
	if (substr($screen_name, 0, 5) === "photo")
	{
		return true;
	}

	// links like botXXXXX is not allowed
	if (substr($screen_name, 0, 3) === "bot")
	{
		return true;
	}

	// checking user by id. If not found - link is free.
	$result = resolve_id_by_name($connection, $screen_name);
	if ($result)
		return true;

	// link is free. OK!
	return false;*/

	return Project::isLinkUsed($screen_name);
}

?>