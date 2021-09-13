<?php

/**
 * Here will go functions with cookies
*/

/**
 * Pays cookie for another user
 *
 * @return true if ok or int with error code
 *
 * Parameters:
 * @param $from_id - from id (current user id)
 * @param $to_id - to id (another user)
 * @param $amount - integer, sum of cookies
 * @param $comment - optional
 *
 * Error codes
 * -1 - incorrect amount
 * -2 - user not exists
 * -3 - not enough cookies
 * -4 - you have been blacklisted by this user
*/
function pay_cookies ($connection, $from_id, $to_id, $amount, $comment = "")
{
	// connecting modules
	if (!function_exists('in_blacklist'))
		require __DIR__ . '/users.php';

	// amount limits
	if (intval($amount) <= 0 || $amount > 100000000) return -1;

	// check user existance
	if (!user_exists($connection, $to_id)) return -2;
	if (in_blacklist($connection, $to_id, $from_id)) return -4;

	$res = $connection->prepare("SELECT cookies FROM users.info WHERE id = ? AND is_deleted = 0 LIMIT 1;");
	if ($res->execute([$from_id]))
	{
		$my_cookies = intval($res->fetch(PDO::FETCH_ASSOC)["cookies"]);

		// checking cookies enough
		if (intval($amount) > intval($my_cookies)) return -3;

		$res = $connection->prepare("SELECT cookies FROM users.info WHERE id = ? LIMIT 1;");
		if ($res->execute([$to_id]))
		{
			$her_cookies = intval($res->fetch(PDO::FETCH_ASSOC)["cookies"]);

			return (
				$connection->prepare("UPDATE users.info SET cookies = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([$her_cookies+intval($amount), intval($to_id)]) &&
				$connection->prepare("UPDATE users.info SET cookies = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([$my_cookies-intval($amount), intval($from_id)])
			);
		}

		return false;
	}

	return true;
}

?>