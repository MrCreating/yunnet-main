<?php

require_once __DIR__ . '/../../lib/vk_audio/autoloader.php';

use Vodka2\VKAudioToken\AndroidCheckin;
use Vodka2\VKAudioToken\SmallProtobufHelper;
use Vodka2\VKAudioToken\CommonParams;
use Vodka2\VKAudioToken\TokenReceiver;
use Vodka2\VKAudioToken\MTalkClient;
use Vodka2\VKAudioToken\TwoFAHelper;

/**
 * Here is the file with API for accounts from OTHER social networks.
*/

// VK
define('VK_API_DOMAIN', 'https://api.vk.com/');

/**
 * Getting bound accounts for selected user.
 * @return array() with accounts info.
 *
 * Parameters:
 * @param $user_id - current user id.
*/
function get_accounts ($connection, $user_id, $receiveToken = false)
{
	$result = [
		[
			'type' => 1, 
			'bound' => false
		]
	];

	$tokens = [];

	$res = $connection->prepare('SELECT token FROM users.accounts WHERE owner_id = ? AND type = ? AND is_active = 1 LIMIT 1;');

	if ($res->execute([intval($user_id), 1]))
	{
		$token = $res->fetch(PDO::FETCH_ASSOC)['token'];
		if ($token)
		{
			if ($receiveToken)
			{
				$tokens[] = $token;

				return $tokens;	
			}

			$result[0] = [
				'type' => 1,
				'bound' => true
			];

			$user_data = json_decode(file_get_contents(VK_API_DOMAIN . 'method/users.get?v=5.131&access_token=' . $token), true);
			if ($user_data['error'])
			{
				if ($user_data['error']['error_code'] === 2)
				{
					delete_account($connection, $user_id, 1);
				}
			}

			$result[0]['first_name'] = $user_data['response'][0]['first_name'];
			$result[0]['last_name']  = $user_data['response'][0]['last_name'];
			$result[0]['user_id']    = $user_data['response'][0]['user_id'];
		}
	}

	return $result;
}

/**
 * Bounds a new account
 * @return true if ok or int with error code.
 * 
 * Parameters:
 * @param $login - account login
 * @param $password - account password
 * @param $type - account type.
 * @param $authCode (optionally) - if service has 2FA - code here.
 *
 * Account types:
 * VK           - 1
 * YANDEX MUSIC - 2
*/
function add_account ($connection, $login, $password, $owner_id, $type = 1, $authCode = '')
{
	if ($type === 1)
	{
		if (get_accounts($connection, $owner_id)[0]['bound'])
		{
			return true;
		}

		$params = new CommonParams();
		$protoHelper = new SmallProtobufHelper();

		$checkin = new AndroidCheckin($params, $protoHelper);
		$authData = $checkin->doCheckin();

		$mtalkClient = new MTalkClient($authData, $protoHelper);
		$mtalkClient->sendRequest();

		unset($authData['idStr']);
		$receiver = new TokenReceiver($login, $password, $authData, $params, $authCode);

		try {
			$token    = $receiver->getToken();
			$acc_type = 1;

			$res = $connection->prepare('INSERT INTO users.accounts (owner_id, type, token) VALUES (:owner_id, :type, :token);');

			$res->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
			$res->bindParam(':type',     $acc_type, PDO::PARAM_INT);
			$res->bindParam(':token',    $token,    PDO::PARAM_STR);

			return $res->execute();
		} catch (Exception $e) {
			if ($e->code === \Vodka2\VKAudioToken\TokenException::TWOFA_REQ && isset($e->extra->validation_sid)) {
				try {
					(new TwoFAHelper($params))->validatePhone($e->extra->validation_sid);
			        
			     	return 10;
				} catch (Exception $e) {
					if ($e->code === 5)
					{
					   	return 20;
					}
				}
			}

			if ($e->code === 2)
			{
			    return 5;
			}
		}
	}

	// no operations done.
	return false;
}

/**
 * Deletes account
 * @return true if ok or int with error code.
 *
 * Parameters
 * @param $owner_id - user id for deletion
 * @param $account_type - account type.
*/
function delete_account ($connection, $owner_id, $account_type = 1)
{
	return $connection->prepare('UPDATE users.accounts SET is_active = 0 WHERE owner_id = ? AND type = ?;')->execute([intval($owner_id), intval($account_type)]);
}

?>