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
		$known_sig = hash_hmac('sha1', file_get_contents('php://input'), $known_github_secret);

		die(var_dump(hash_equals($known_sig, $signature_parts[1])));
	}
}

?>