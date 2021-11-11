<?php

$endpoint_id         = 0;
$known_github_secret = 'yunnet-response-is-ok';

$headers = getallheaders();
if (isset($headers['X-Hub-Signature']))
{
	$signature = $headers['X-Hub-Signature'];

	$signature_parts = explode('=', $signature);
	if (count($signature_parts) === 2)
	{
		$known_sig   = hash_hmac('sha1', file_get_contents('php://input'), $known_github_secret);
		$endpoint_id = intval(hash_equals($known_sig, $signature_parts[1]));
	}
}

// user endpoint
if ($endpoint_id === 0)
{
	if (($user = Context::get()->getCurrentUser()) && isset($user) && ($user->getAccessLevel() >= 4))
	{
		die(require_once __DIR__ . '/users/index.php');
	}
}

// github endpoint
if ($endpoint_id === 1)
{
	die(require_once __DIR__ . '/github/index.php');
}

die(header('Location: ' . Project::DEFAULT_URL));
?>