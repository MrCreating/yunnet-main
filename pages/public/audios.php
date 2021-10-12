<?php

require_once __DIR__ . '/../../bin/functions/accounts.php';
require_once __DIR__ . '/../../bin/functions/audios.php';

if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

	if (!$context->allowToUseUnt()) die(json_encode(array('error' => 1)));

	$accountType = intval($_POST['type']);

	switch ($action) {
		case 'bound_account':
			if ($accountType === 1)
			{
				$login = strval($_POST['login']);
				$password = strval($_POST['password']);
				$code = intval($_POST['auth_code']) > 0 ? strval($_POST['auth_code']) : '';

				$result = add_account($connection, $login, $password, $context->getCurrentUser()->getId(), 1, $code);
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
				$result = delete_account($connection, $context->getCurrentUser()->getId(), $accountType);
				if ($result === true)
					die(json_encode(array('success'=>1)));

				if ($result === false)
					die(json_encode(array('success'=>0)));

				die(json_encode(array('error'=>intval($result))));
			}
		break;

		case 'get_audio':
			$currentOfffset = intval($_POST['offset']);
			$audiosCount = intval($_POST['count']);

			$audios_list = get_audio($connection, $context->getCurrentUser()->getId(), 1, $currentOfffset, $audiosCount);
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