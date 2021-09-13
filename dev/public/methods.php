<?php

/**
 * API methods list.
*/

if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

	if ($action === 'get_methods_list')
	{
		$methods_list = get_registered_methods();

		die(json_encode($methods_list));
	}
	if ($action === 'get_method_info')
	{
		$dev_language = get_dev_language($connection);

		$params_list = get_params_list($_POST['method_name']);
		if ($params_list === false)
			die(json_encode(array('error'=>1)));

		$params = [];
		foreach ($params_list as $key => $value) 
		{
			$params[] = $key;
		}

		$method_info = [
			'header' => $_POST['method_name'],
			'description' => $dev_language[$_POST['method_name']],
			'params' => $params,
			'returns' => 'test3'
		];

		die(json_encode($method_info));
	}
}

?>