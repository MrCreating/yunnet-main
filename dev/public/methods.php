<?php

/**
 * API methods list.
*/

use unt\objects\Request;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	if ($action === 'get_methods_list')
	{
		$methods_list = get_registered_methods();

		die(json_encode($methods_list));
	}
	if ($action === 'get_method_info')
	{
		$dev_language = []; //get_dev_language($connection);

		$params_list = get_params_list(Request::get()->data['method_name']);
		if ($params_list === false)
			die(json_encode(array('error'=>1)));

		$params = [];
		foreach ($params_list as $key => $value) 
		{
			$params[] = $key;
		}

		$method_info = [
			'header' => Request::get()->data['method_name'],
			'description' => $dev_language[Request::get()->data['method_name']],
			'params' => $params,
			'returns' => 'test3'
		];

		die(json_encode($method_info));
	}
}

?>
