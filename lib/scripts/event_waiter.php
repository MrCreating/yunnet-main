<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$user_queue = 'user_1';
$session = 'r_h5578f_r9_i1agnwus2s3eqgkdlj6juaewb97_3wlyyw52hd_session';

$connection = new AMQPStreamConnection('rabbit_mq', 5672, getenv('RABBITMQ_DEFAULT_USER'), getenv('RABBITMQ_DEFAULT_PASS'));

$channel = $connection->channel();
$channel->exchange_declare($user_queue, 'fanout', false, false, false);
$channel->queue_declare($session, false, false, false, false);
$channel->queue_bind($session, $user_queue);

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
};

$channel->basic_consume($session, '', false, false, false, false, $callback);

while ($channel->is_open) {
    $channel->wait();
}

$channel->close();
$connection->close();

echo '[x] Waiting for messages...' . PHP_EOL;