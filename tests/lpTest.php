<?php

require __DIR__ . '/../bin/event_manager.php';

$emitter = new EventEmitter();

die(var_dump($emitter->sendEvent([1], [1], ['event' => 'test'], 1)));

?>