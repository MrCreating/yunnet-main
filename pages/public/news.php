<?php
if (!function_exists('get_news'))
	require __DIR__ . "/../../bin/functions/wall.php";

if (isset($_POST['action']))
{
	if (!$context->isLogged())
		die(json_encode(array('unauth' => 1)));

	$action = strtolower($_POST['action']);
	if ($action === "get_posts")
	{
		if ($context->getCurrentUser()->isBanned())
			die(json_encode(array('error'=>1)));
		
		$posts = get_news($connection, $context->getCurrentUser()->getId(), $context->getCurrentUser()->getId());

		die(json_encode($posts));
	}
}
?>