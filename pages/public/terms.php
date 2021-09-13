<?php

if (isset($_POST['action']))
{
	$action = strtolower(trim($_POST['action']));

	if ($action === "get_terms_text")
	{
		$termsText = get_terms_text();

		die(json_encode(array('terms'=>$termsText)));
	}
}

?>