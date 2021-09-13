<?php

/**
 * Functions for developers (API, bots, etc)
*/
function get_registered_methods ()
{
	return [
		'account'       => ['closeProfile', 'getSettings', 'isScreenNameUsed', 'setPrivacySettings', 'setProfileData', 'toggleNotificationSetting', 'toggleSoundSetting', 'updatePhoto', 'updateScreenName'],
		'auth'          => ['get'],
		'chats'         => ['addUser', 'create', 'get', 'getHistory', 'getInfo', 'getInfoByLink', 'joinByLink', 'muteUser', 'removeUser', 'setLink', 'setMemberLevel', 'setPermission', 'updatePhoto', 'updateTitle'],
		'friends'       => ['add', 'block', 'delete', 'get', 'getBlacklisted', 'getOutcoming', 'getState', 'getSubscribers'],
		'likes'         => ['add', 'remove', 'getLiked', 'getLikersList'],
		'messages'      => ['delete', 'edit', 'getById', 'send'],
		'news'          => ['get'],
		'notifications' => ['get', 'getById', 'read'],
		'realtime'      => ['connect'],
		'uploads'       => ['getUploadQuery', 'uploadFile'],
		'users'         => ['get', 'resolveScreenName', 'search'],
		'wall'          => ['get', 'getById', 'createPost', 'editPost', 'commentPost', 'editComment', 'deletePost', 'deleteComment']
	];
}

function get_params_list ($method_name)
{
	$method_data = explode('.', $method_name);

	$method_group = basename($method_data[0]);
	$method_name  = basename($method_data[1]);

	$registered_methods = get_registered_methods();
	if (!isset($registered_methods[$method_group]) || !in_array($method_name, $registered_methods[$method_group]))
	{
		return false;
	}

	try 
	{
		$only_params = true;
		require __DIR__ . '/../../api/methods/' . $method_group . '/' . $method_name . '.php';

		return $params;
	} catch (Exception $error)
	{
		return false;
	}
}

/**
 * Create API JSON Error
*/
function create_json_error ($error_code, $error_description, $addidional_info = null)
{
	$result = [
		'error'  => [
			'error_code'    => intval($error_code),
			'error_message' => strval($error_description)
		],
		'params'       => [],
		'request_type' => $_SERVER["REQUEST_METHOD"]
	];

	$currentIndex = 0;

	foreach ($_REQUEST as $key => $value) 
	{
		$currentIndex++;
		if ($key !== "key" && $key !== "auth")
		{
			$result['params'][] = [
				"key"   => $key,
				"value" => $value
			];
		}

		if ($currentIndex > 100) break;
	}

	if ($addidional_info && is_array($addidional_info))
	{
		$result['data'] = $addidional_info;
	}

	return json_encode($result);
}

?>