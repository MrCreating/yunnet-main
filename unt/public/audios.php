<?php

require_once __DIR__ . '/../../bin/functions/accounts.php';
require_once __DIR__ . '/../../bin/functions/audios.php';

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	$accountType = intval(Request::get()->data['type']);

	switch ($action) {
		case 'bound_account':
			if ($accountType === 1)
			{
				$login = strval(Request::get()->data['login']);
				$password = strval(Request::get()->data['password']);
				$code = intval(Request::get()->data['auth_code']) > 0 ? strval(Request::get()->data['auth_code']) : '';

				$result = add_account($connection, $login, $password, Context::get()->getCurrentUser()->getId(), 1, $code);
				if ($result === true)
					die(json_encode(array('success' => 1)));

				if ($result === false)
					die(json_encode(array('success' => 0)));

				die(json_encode(array('error' => intval($result))));
			}
		break;

		case 'unbound_account':
			if ($accountType === 1)
			{
				$result = delete_account($connection, Context::get()->getCurrentUser()->getId(), $accountType);
				if ($result === true)
					die(json_encode(array('success'=>1)));

				if ($result === false)
					die(json_encode(array('success'=>0)));

				die(json_encode(array('error'=>intval($result))));
			}
		break;

		case 'get_audio':
			$currentOfffset = intval(Request::get()->data['offset']);
			$audiosCount = intval(Request::get()->data['count']);

			$audios_list = get_audio($connection, Context::get()->getCurrentUser()->getId(), 1, $currentOfffset, $audiosCount);
			if ($audios_list === false)
				die(json_encode(array('error'=>1)));

			die(json_encode(array('response'=>$audios_list)));
		break;
		
		default:
		break;
	}
	
	die(json_encode(array('error' => 1)));
}

?>