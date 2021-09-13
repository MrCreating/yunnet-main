<?php
// checking login data
if (isset($_POST['action']))
{
	if ($context->isLogged())
		die(json_encode(array("error" => 1)));

	$action = strtolower($_POST['action']);
	if ($action === 'login')
	{
		require_once __DIR__ . '/../../bin/functions/auth.php';

		header('Access-Control-Allow-Origin: '.get_page_origin());
		header('Access-Control-Allow-Credentials: true');

		// auth result. It is array which contains id field.
		$res = auth_user($connection, $_POST['email'], $_POST['password']);
		if (!$res)
		{
			die(json_encode(array("error"=>1)));
		}
		else
		{	
			die(json_encode(array("success"=>array("redirect_url"=>($_REQUEST["to"] ? "/".$_REQUEST["to"] : "/")))));
		}
	}
}
?>