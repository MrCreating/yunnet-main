<?php

if (isset($_POST['action']))
{
	$action = strtolower(trim($_POST['action']));

	if ($action === "get_rules_text")
	{
		$rulesText = get_rules_text();

		die(json_encode(array('rules'=>$rulesText)));
	}
}

?>