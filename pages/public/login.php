<?php

require_once __DIR__ . '/../../bin/functions/auth.php';

if (isset($_POST['action']))
{
	$action = strtolower($_POST['action']);

	if ($context->allowToUseUnt()) die(json_encode(array("error" => 1)));

	if ($action === 'login')
	{
		header('Access-Control-Allow-Origin: '.get_page_origin());
		header('Access-Control-Allow-Credentials: true');

		// auth result. It is array which contains id field.
		$res = auth_user($connection, $_POST['email'], $_POST['password']);
		if (!$res)
		{
			die(json_encode(array("error" => 1)));
		}
		else
		{	
			die(json_encode(array("success" => array("redirect_url" => ($_REQUEST["to"] ? "/".$_REQUEST["to"] : "/")))));
		}
	}
}

?>