<?php

/**
 * Here will functions for
 * theme management and other ui functions
*/

/**
 * Gets menu item ids list.
 * @return array of ids.
 *
 * Parameters:
 * @param $user_id - user_id who gets menu ids.
 *
 * IDS:
 * 1 - news
 * 2 - notifications
 * 3 - messages
 * 4 - friends
 * 5 - settings
 * 6 - audios
*/
function get_menu_items_data ($connection, $user_id)
{
	$default_item_ids = [
		1, 2, 3, 4, 5, 6, 7, 8
	];

	$item_ids = array();
	$res = $connection->prepare("SELECT themes FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id)]);

	$menu_ids = unserialize($res->fetch(PDO::FETCH_ASSOC)["themes"])["menu"];
	foreach ($default_item_ids as $index => $menu_id)
	{
		$item = $menu_ids[$index];
		if (!$item)
			$item = intval($menu_id);

		if (!in_array($item, $item_ids))
			$item_ids[] = $item;
	}

	if (count($menu_ids) != count($default_item_ids))
	{
		foreach ($default_item_ids as $index => $menu_id) {
			if (!in_array($menu_id, $item_ids))
				$item_ids[] = $menu_id;
		}
	}

	return $item_ids;
}

/**
 * Sets a new item ids list;
 * @return true if ok.
 * 
 * Parameters:
 * @param $user_id - user_id for who must be set new items
 * @param $menu_ids - array with new item ids.
*/
function set_menu_items ($connection, $user_id, $menu_ids)
{
	$default_item_ids = [
		1, 2, 3, 4, 5, 6, 7, 8
	];

	// in items ids must only have unique items from 1 to 6.
	$item_ids = array();
	foreach ($default_item_ids as $index => $menu_id)
	{
		$item = $menu_ids[$index];
		if (!$item)
			$item = intval($menu_id);

		if (!in_array($item, $item_ids))
			$item_ids[] = $item;
	}

	if (count($menu_ids) != count($default_item_ids))
	{
		foreach ($default_item_ids as $index => $menu_id) {
			if (!in_array($menu_id, $item_ids))
				$item_ids[] = $menu_id;
		}
	}

	// all preparations is ok - set new menu.
	$res = $connection->prepare("SELECT themes FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id)]);
	$themes = unserialize($res->fetch(PDO::FETCH_ASSOC)["themes"]);

	$themes["menu"] = $item_ids;
	$themes = serialize($themes);

	$res = $connection->prepare("UPDATE users.info SET themes = :themes WHERE id = :user_id LIMIT 1;");

	$res->bindParam(":themes",  $themes,  PDO::PARAM_STR);
	$res->bindParam(":user_id", $user_id, PDO::PARAM_INT);

	// return state;
	return $res->execute();
}

/**
 * Get themes list of selected user
 * @return array with Theme classes objects
 *
 * Parameters:
 * @param $user_id - user_id who list must be provided
*/
function get_themes ($connection, $user_id, $count = 30, $offset = 0)
{
	// connecting modules
	if (!class_exists('Theme'))
		require __DIR__ . "/../objects/theme.php";

	return Theme::getList(intval($count), intval($offset));
}

/**
 * Gets current theme credentials of user_id
 * @return theme data
 *
 * Parameters:
 * @param $user_id - user_id who theme must be get
*/
function get_current_theme_credentials ($connection, $user_id)
{
	$res = $connection->prepare("SELECT current_theme FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id)]);

	return $res->fetch(PDO::FETCH_ASSOC)["current_theme"];
}

/**
 * Create a theme.
 * @return true if ok or false if error.
 *
 * Parameters:
 * @param $owner_id - user_id of new owner of theme
 * @param $title - theme title (32-length max)
 * @param $description - description of theme
 * @param $is_private - private theme or not.
*/
function create_theme ($connection, $owner_id, $title, $description, $is_private = 0, $is_default = 0)
{
	if (!class_exists('Theme'))
		require __DIR__ . "/../objects/theme.php";

	return Theme::create(strval($title), strval($description), boolval($is_private));
}

/**
 * Update theme data.
 * Can update title, description and mode.
 * @return true if ok or false if error.
 *
 * Parameters:
 * @param $theme - Theme object.
 * @param $updater_id - who updates the theme
 * @param $new_title - new title,
 * @param $new_description - new description
 * @param $private_mode - private mode flag
*/
function update_theme ($connection, $theme, $updater_id, $new_title, $new_description, $private_mode)
{
	return $theme->setTitle($new_title)->setDescription($new_description)->setPrivate($private_mode)->apply();
}

// update theme code
// return false if unknown error
// return string with error message if error has a message
// return true if theme updated successfully
function update_theme_code ($theme, $updater_id, $code_type, $code)
{
	if (!$theme->valid() || $theme->getOwnerId() !== $updater_id) return false;

	$code_type = strtolower($code_type);
	if ($code_type !== "js" && $code_type !== "css")
		return false;

	if (is_empty($code))
		return false;

	if ($code_type === "js")
	{
		return $theme->setJSCode($code);
	}
	if ($code_type === "css")
	{
		return $theme->setCSSCode($code);
	}

	return false;
}

/**
 * Parse the keyboard for messages and not only
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $Keyboard - array keyboard
*/
function parse_keyboard ($keyboard)
{
	$keyboard_data = $keyboard["keyboard"];
	$params_data   = $keyboard["params"];

	$ids = [
		1 => SEND_MESSAGE
	];

	// keybard and params is required
	if (!$keyboard_data || !$params_data) return false;

	// max 4 lines
	if (count($keyboard_data) > 4) return false;

	foreach ($keyboard_data as $index => $item) {
		// max 4 buttons on line
		if (count($item) > 4) return false;

		foreach ($item as $number => $button) {
			$id         = $ids[intval($button["id"])] ? intval($button["id"]) : NULL;
			$color      = parse_hex($button["color"]);
			$text       = !(is_empty($button["text"]) || strlen($button["text"]) > 256) ? strval($button["text"]) : NULL;
			$text_color = parse_hex($button["textColor"]);

			// all keyboard must be valid.
			if (!$id || !$color || !$text)
			{
				return [false, $index, $number];
			}
		}
	}

	$additional_data = (!is_empty($keyboard['params']['data']) && strlen($keyboard['params']['data']) <= 1000) ? strval($keyboard['params']['data']) : NULL;

	$keyboard['params'] = [
		'oneTime'  => $keyboard['params']['oneTime'] === true ? true : false,
		'autoShow' => $keyboard['params']['autoShow'] === true ? true : false
	];

	if ($additional_data)
		$keyboard['params']['data'] = $additional_data;

	return $keyboard;
}

/**
 * Parse HEX
 * @return hex code if ok or false if error
 *
 * Parameters:
 * @param $hex - HEX code
*/
function parse_hex ($hex)
{
	// it must be array
	if (!is_array($hex)) return false;

	// max array length - 3
	if (count($hex) != 3) return false;

	$colors = [
		intval($hex[0]),
		intval($hex[1]),
		intval($hex[2])
	];
	
	// OK!
	return $colors;
}

/**
 * Apply a theme. Nee s event_emitter
 * @return true if ok or false if error
 *
 * Paramerters:
 * @param $theme - Theme instance.
*/
function apply_theme ($connection, $user_id, $theme = false)
{
	if ($theme && $theme->valid())
		return $theme->setAsCurrent();

	if (!$theme)
		return Theme::reset();

	return false;
}

/**
 * Checks if JS allowed in themes.
 * @return true if allowed or false if not.
 *
 * Parameters:
 * @param $user_id - check user id.
 */
function themes_js_allowed ($connection, $user_id) 
{
	$res = $connection->prepare("SELECT themes_allow_js FROM users.info WHERE id = ? LIMIT 1;");
	if ($res->execute([$user_id]))
	{
		return boolval(intval($res->fetch(PDO::FETCH_ASSOC)["themes_allow_js"]));
	}

	return false;
}

/**
 * Toggle JS allowance
 * @return true if toggle or false if not.
 *
 * Parameters:
 * @param $user_id - user if for toggle 
 */
function toggle_js_allowance ($connection, $user_id)
{
	$new_mode = !themes_js_allowed($connection, $user_id);

	if ($connection->prepare("UPDATE users.info SET themes_allow_js = ? WHERE id = ? LIMIT 1;")->execute([intval($new_mode), $user_id])) 
	{
		return intval($new_mode);
	}

	return false;
}

/**
 * Deletes a theme
 * @return true if ok or false if not
 *
 * Parameters:
 * @param int $user_id  - current user id.
 * @param int $owner_id - owner id of theme
 * @param int $theme_id - theme identifier.
*/
function delete_theme ($connection, $user_id, $owner_id, $theme_id)
{
	// connecting modules
	if (!class_exists('Theme'))
		require __DIR__ . "/../objects/theme.php";

	$theme = new Theme(intval($owner_id), intval($theme_id));

	return $theme->delete();
}
?>