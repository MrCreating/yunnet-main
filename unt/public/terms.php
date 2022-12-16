<?php

use unt\objects\Request;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(trim(Request::get()->data['action']));

	if ($action === "get_terms_text")
	{
		$termsText = unt\functions\get_terms_text();

		die(json_encode(array('terms'=>$termsText)));
	}
}

?>