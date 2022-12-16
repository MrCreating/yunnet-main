<?php

namespace unt\functions\theming;

use PDO;
use unt\objects\Theme;
use unt\platform\DataBaseManager;

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
function get_menu_items_data ($connection, $user_id): array
{
	$default_item_ids = [
		1, 2, 3, 4, 5, 6, 7, 8
	];

	$item_ids = array();
	$res = DataBaseManager::getConnection()->prepare("SELECT themes FROM users.info WHERE id = ? LIMIT 1;");
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
	$res = DataBaseManager::getConnection()->prepare("SELECT themes FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id)]);
	$themes = unserialize($res->fetch(PDO::FETCH_ASSOC)["themes"]);

	$themes["menu"] = $item_ids;
	$themes = serialize($themes);

	$res = DataBaseManager::getConnection()->prepare("UPDATE users.info SET themes = :themes WHERE id = :user_id LIMIT 1;");

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
	$res = DataBaseManager::getConnection()->prepare("SELECT current_theme FROM users.info WHERE id = ? LIMIT 1;");
	$res->execute([intval($user_id)]);

	return $res->fetch(PDO::FETCH_ASSOC)["current_theme"];
}

/**
 * Create a theme.
 * @return Theme if ok or false if error.
 *
 * Parameters:
 * @param $owner_id - user_id of new owner of theme
 * @param $title - theme title (32-length max)
 * @param $description - description of theme
 * @param $is_private - private theme or not.
*/
function create_theme ($connection, $owner_id, $title, $description, $is_private = 0, $is_default = 0)
{
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

	if (\unt\functions\is_empty($code))
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
 * Apply a theme. Nee s event_emitter
 * @param bool $theme - Theme instance.
*@return true if ok or false if error
 *
 * Paramerters:
 */
function apply_theme ($connection, $user_id, bool $theme = false): bool
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
function themes_js_allowed ($connection, $user_id): bool
{
	$res = DataBaseManager::getConnection()->prepare("SELECT themes_allow_js FROM users.info WHERE id = ? LIMIT 1;");
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
function toggle_js_allowance ($connection, $user_id): bool
{
	$new_mode = !themes_js_allowed($connection, $user_id);

	if (DataBaseManager::getConnection()->prepare("UPDATE users.info SET themes_allow_js = ? WHERE id = ? LIMIT 1;")->execute([intval($new_mode), $user_id]))
	{
		return intval($new_mode);
	}

	return false;
}

/**
 * Deletes a theme
 * @param int $user_id  - current user id.
 * @param int $owner_id - owner id of theme
 * @param int $theme_id - theme identifier.
*@return true if ok or false if not
 *
 * Parameters:
 */
function delete_theme ($connection, int $user_id, int $owner_id, int $theme_id)
{
	$theme = new Theme(intval($owner_id), intval($theme_id));

	return $theme->delete();
}
?>