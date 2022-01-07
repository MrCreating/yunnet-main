<?php

if (isset(Request::get()->data['action']))
{
	$action = strtolower(trim(Request::get()->data['action']));

	if ($action === "get_terms_text")
	{
		$termsText = get_terms_text();

		die(json_encode(array('terms'=>$termsText)));
	}
}

?>