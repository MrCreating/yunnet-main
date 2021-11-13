<?php

$result = (new EventEmitter())->sendEvent([1], [0], [
	'event' => 'github_event',
	'data'  => file_get_contents('php://input')
]);

die(json_encode(array('response' => intval($response))));
?>