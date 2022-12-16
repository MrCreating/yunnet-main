<?php

use unt\objects\Request;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(trim(Request::get()->data['action']));

	if ($action === "get_rules_text")
	{
		$rulesText = unt\functions\get_rules_text();

		die(json_encode(array('rules'=>$rulesText)));
	}
}

?>