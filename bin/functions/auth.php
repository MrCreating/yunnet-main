<?php
if (!class_exists('Entity'))
	require __DIR__ . '/../objects/entities.php';
if (!class_exists('Session'))
	require __DIR__ .'/../objects/session.php';

/**
 * file which contains auth methods!!!
 * all functions returns user info and token.
*/

/**
 * auth by access token
 * @return array woth data of this token or false if error exceeded.
 */
function auth_by_token ($connection, $token)
{
	// connecting modules.
	if (!class_exists('Entity'))
		require __DIR__ . "/../objects/entities.php";

	// checking token existence
	$res = $connection->prepare("SELECT permissions, owner_id FROM apps.tokens WHERE token = :token AND is_deleted = 0 LIMIT 1;");

	$res->bindParam(":token", $token, PDO::PARAM_STR);
	if ($res->execute())
	{
		// if token exists - fetch it.
		$data = $res->fetch(PDO::FETCH_ASSOC);
		if (!$data["owner_id"] || !$data["permissions"])
			return false;

		// resolve data and return result.
		return [
			'user_id'      => intval($data["owner_id"]),
			'permissions'  => explode(',', $data["permissions"]),
			'owner_object' => $data["owner_id"] > 0 ? new User($data["owner_id"]) : new Bot($data["owner_id"]*-1)
		];
	}

	// auth failed.
	return false;
}

/**
 * creates an app
 * @return App instance of created app or false in error
*/
function create_app ($connection, $owner_id, $title)
{
	if (!class_exists('App'))
		require __DIR__ . '/../objects/app.php';

	return App::create($title);
}

/**
 * Creates a token
 * @return token and id of new token.
 *
 * Parameters:
 * @param $owner_id - user_id who will be a new owner of token
 * @param $app_id - app_id if token does not for bot.
 * @param $permissions - array of the permissions for the token.
 *
 * Permissions:
 * 1 - friends
 * 2 - messages
 * 3 - settings
 * 4 - management (create tokens, sessions, etc)
*/
function create_token ($connection, $owner_id, $app_id, $permissions = ["1", "2", "3", "4"])
{
	// connecting modules.
	if (!class_exists('App'))
		require __DIR__ . "/../objects/app.php";

	// if token for bot - $app wil be null;
	$app = $app_id > 0 ? new App(intval($app_id)) : NULL;

	// if app has been created checks for validity.
	if (!$app->valid()) return false;

	// creating new key and saving permissions
	$key = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'.rand(100000, 999999).'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz'.rand(1000, 9999999)), 0, 75);

	$permissions = implode(',', $permissions);

	// inserting into DB.
	$res = $connection->prepare("INSERT INTO apps.tokens (app_id, owner_id, token, permissions) VALUES (:app_id, :owner_id, :token, :permissions);");
	$res->bindParam(":app_id",      $app_id,      PDO::PARAM_INT);
	$res->bindParam(":owner_id",    $owner_id,    PDO::PARAM_INT);
	$res->bindParam(":token",       $key,         PDO::PARAM_STR);
	$res->bindParam(":permissions", $permissions, PDO::PARAM_STR);
	if ($res->execute())
	{
		// if OK - resolves an id and return a token with id.
		$res = $connection->prepare("SELECT LAST_INSERT_ID()");
		$res->execute();
		$token_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);
		return [
			'token' => $key,
			'id'    => $token_id
		];
	}

	// failed.
	return false;
}

/**
 * Resolves tokens list
 * @return array of data.
 *
 * Parameters:
 * @param $owner_id - user_id which tokens must be got.
*/
function get_tokens_list ($connection, $owner_id, $app_id = 0)
{
	$res = $connection->prepare("SELECT id, app_id, token, permissions FROM apps.tokens WHERE is_deleted != 1 AND owner_id = ? LIMIT 100;");
	
	$params = [intval($owner_id)];
	$result = [];

	if ($app_id > 0)
	{
		$res = $connection->prepare("SELECT id, app_id, token, permissions FROM apps.tokens WHERE is_deleted != 1 AND owner_id = ? AND app_id = ? LIMIT 100;");

		$params = [intval($owner_id), intval($app_id)];
	}

	if ($res->execute($params))
	{
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		foreach ($data as $index => $token_info)
		{
			$tmp = explode(',', $token_info['permissions']);
			$permissions = [];

			foreach ($tmp as $index => $permission) {
				$permissions[] = intval($permission);
			}

			$object = [
				'id'          => intval($token_info['id']),
				'app_id'      => intval($token_info['app_id']),
				'token'       => strval($token_info['token']),
				'permissions' => $permissions
			];

			$result[] = $object;
		}
	}

	return $result;
}

/**
 * user's auth
 * @return array with user_id.
 */
function auth_user ($connection, $email, $password)
{
	if (!function_exists('create_notification'))
		require __DIR__ . '/notifications.php';

	// checking by email
	$res = $connection->prepare("SELECT id FROM users.info WHERE email = ? AND is_deleted = 0 LIMIT 1;");
	$res->execute([$email]);
	$id = $res->fetch(PDO::FETCH_ASSOC)['id'];

	// if user not found.
	if (!$id) return false;

	// now we getting hash or created or set password
	$res = $connection->prepare("SELECT password FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1;");
	$res->execute([$id]);
	$real_password = $res->fetch(PDO::FETCH_ASSOC)['password'];
	
	// if OK - return user_id
	$success = password_verify($password, $real_password);
	if (!$success) return false;

	create_notification($connection, $id, "account_login", [
		'ip'   => $_SERVER['REMOTE_ADDR'],
		'time' => time()
	]);

	$result = Session::start(intval($id));
	if ($result && $result->setAsCurrent())
	{
		return ['user_id' => intval($id)];
	}

	return false;
}

/**
 * Verifies the password
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - who password check
 * @param $old_password - check password.
 * @param $new_password (optionally) - if verify new password too.
*/
function check_password ($connection, $user_id, $old_password, $new_password = null)
{
	// password may be longer than 6.
	if (is_empty($old_password) || strlen($old_password) < 6 || strlen($old_password) > 64)
		return false;

	if ($new_password)
	{
		if (is_empty($new_password) || strlen($new_password) < 6 || strlen($old_password) > 64)
			return false;
	}

	$res = $connection->prepare("SELECT password FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1;");
	$res->execute([intval($user_id)]);

	// old account password
	$old_password_hash = strval($res->fetch(PDO::FETCH_ASSOC)["password"]);
	
	// return result;
	return password_verify($old_password, $old_password_hash);
}

/**
 * Changes the password
 * @return true if ok or false if error
 *
 * Parameters:
 * @param $user_id - who password change
 * @param $old_password - check old password.
 * @param $new_password - new password to setup
*/
function change_password ($connection, $user_id, $old_password, $new_password)
{
	// new password hash
	$new_password = password_hash($new_password, PASSWORD_DEFAULT);

	// updating the password
	$res = $connection->prepare("UPDATE users.info SET password = :new_password WHERE id = :user_id AND is_deleted = 0 LIMIT 1;");
	$res->bindParam(":new_password", $new_password, PDO::PARAM_STR);
	$res->bindParam(":user_id",      $user_id,      PDO::PARAM_INT);
	
	if ($res->execute())
	{
		return $connection->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE owner_id = ?;")->execute([$user_id]);
	}

	// error
	return false;
}

/**
 * Gets apps list.
 * @return array() with App instances.
 *
 * Parameters:
 * @param int $user_id - current user id.
 * @param int $offset - apps offset
 * @param int $count - apps count for response
*/
function get_apps_list ($connection, $user_id, $offset = 0, $count = 30)
{
	if ($offset < 0) $offset = 0;
	if ($count > 1000) $count = 100;
	if ($count < 0) $count = 1;

	$result = [];
	if (!class_exists('App'))
		require __DIR__ . '/../objects/app.php';

	$res = $connection->prepare("SELECT id FROM apps.info WHERE is_deleted != 1 AND owner_id = ? LIMIT ".intval($offset).", ".intval($count).";");

	if ($res->execute([intval($user_id)]))
	{
		$apps_identifiers = $res->fetchAll(PDO::FETCH_ASSOC);
		foreach ($apps_identifiers as $index => $app_info) {
			$app_id = intval($app_info['id']);

			$app = new App($app_id);
			if (!$app->valid()) continue;

			$result[] = $app;
		}
	}

	return $result;
}

/**
 * Gets info of API token by id.
 * @return Array with token info or false on error
 *
 * Parameters:
 * @param int $token_id - token id for resolving
*/
function get_token_by_id ($connection, $token_id)
{
	$res = $connection->prepare('SELECT id, owner_id, token, permissions FROM apps.tokens WHERE id = ? LIMIT 1;');

	if ($res->execute([intval($token_id)]))
	{
		$data = $res->fetch(PDO::FETCH_ASSOC);

		if (!$data) return false;

		$permissions = [];
		$tmp = explode(',', $data['permissions']);

		foreach ($tmp as $index => $permission) {
			$permissions[] = intval($permission);
		}

		return [
			'id'          => intval($data['id']),
			'owner_id'    => intval($data['owner_id']),
			'token'       => intval($data['token']),
			'permissions' => $permissions
		];
	}

	return false;
}

/**
 * Deletes selected token
 * @return true if ok or false if not
 *
 * Parameters:
 * @param int $token_id - token identifier.
*/
function delete_token ($connection, $token_id)
{
	return $connection->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE id = ? LIMIT 1;")->execute([intval($token_id)]);
}

/**
 * Updates the token info
 * @return true if ok or false if not
 *
 * Parameters:
 * @param int $token_id - token identifier
 * @param string $permissions = permissions string
*/
function update_token ($connection, $token_id, $permissions = "")
{
	$res = $connection->prepare("UPDATE apps.tokens SET permissions = :new_permissions WHERE id = :id LIMIT 1;");

	$token_id = intval($token_id);

	$res->bindParam(":new_permissions", $permissions, PDO::PARAM_STR);
	$res->bindParam(":id",              $token_id,    PDO::PARAM_INT);
	
	return $res->execute();
}
?>