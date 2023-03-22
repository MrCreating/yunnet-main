<?php

use unt\objects\Context;
use unt\objects\Project;
use unt\objects\Request;
use unt\objects\User;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);
    if ($action === 'get_page')
    {
        die(\unt\design\Template::get('auth')->show());
    }

	if (Context::get()->allowToUseUnt()) die(json_encode(array("error" => 1)));

	if ($action === 'login')
	{
		header('Access-Control-Allow-Origin: '. Project::getOrigin());
		header('Access-Control-Allow-Credentials: true');

		// auth result. It is array which contains id field.
		$res = User::auth((string)Request::get()->data['email'], (string)Request::get()->data['password']);
		if (!$res)
		{
			die(json_encode(array("error" => 1)));
		}
		else
		{	
			die(json_encode(array("success" => array("redirect_url" => (Request::get()->data["to"] ? "/".Request::get()->data["to"] : "/")))));
		}
	}

	die(json_encode(array("error" => 1)));
}

?>