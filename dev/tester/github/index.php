<?php

/*$_SESSION['user_id'] = 1;

$result = (new EventEmitter())->sendEvent([1], [0], [
	'event' => 'github_event',
	'data'  => json_decode(file_get_contents('php://input'), true)
]);*/

$event = json_decode(file_get_contents('php://input'), false);

die(json_encode(array('response' => 1)));
?>