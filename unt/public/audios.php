<?php

use unt\objects\Context;
use unt\objects\Request;
use unt\platform\DataBaseManager;

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

				$result = Context::get()
                    ->getCurrentUser()
                    ->getSettings()
                    ->getSettingsGroup(\unt\objects\Settings::SERVICES_GROUP)
                    ->addService(\unt\objects\ServicesSettingsGroup::SERVICE_TYPE_VK, $login, $password, $code);

				if ($result)
					die(json_encode(array('success' => 1)));

				if ($result === false)
					die(json_encode(array('success' => 0)));

				die(json_encode(array('error' => intval($result))));
			}
		break;

		case 'unbound_account':
			if ($accountType === 1)
			{
				$result = Context::get()
                    ->getCurrentUser()
                    ->getSettings()
                    ->getSettingsGroup(\unt\objects\Settings::SERVICES_GROUP)
                    ->deleteService(\unt\objects\ServicesSettingsGroup::SERVICE_TYPE_VK);

				if ($result === true)
					die(json_encode(array('success' => 1)));

				if ($result === false)
					die(json_encode(array('success' => 0)));

				die(json_encode(array('error' => intval($result))));
			}
		break;

		case 'get_audio':
			$currentOffset = intval(Request::get()->data['offset']);
			$audiosCount = intval(Request::get()->data['count']);

			$audios_list = \unt\objects\Audio::getListFromService(\unt\objects\ServicesSettingsGroup::SERVICE_TYPE_VK, $currentOffset, $audiosCount);

			die(json_encode(array('response' => $audios_list)));
		break;
		
		default:
		break;
	}
	
	die(json_encode(array('error' => 1)));
}

?>