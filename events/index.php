<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use unt\objects\Project;
use unt\objects\Request;
use unt\platform\EventManager;

header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: ' . Project::getOrigin());

$key = trim(Request::get()->data['key']);
if (!$key)
{
    die(json_encode(array(
        'error' => [
            'error_code' => 500,
            'error_message' => 'Auth key is not provided'
        ]
    )));
}

$eventManager = EventManager::findByKey($key);
if (!$eventManager)
{
    die(json_encode(array(
        'error' => [
            'error_code' => 501,
            'error_message' => 'Auth key is incorrect'
        ]
    )));
}

$eventManager->setKeyActive($key, 1);

$exchange_name = $eventManager->getEntityKey();
$queue_name    = $key . '_session';

$connection = new AMQPStreamConnection('rabbit_mq', 5672, getenv('RABBITMQ_DEFAULT_USER'), getenv('RABBITMQ_DEFAULT_PASS'));
$channel = $connection->channel();

$channel->exchange_declare($exchange_name, 'fanout', false, false, false);
$channel->queue_declare($queue_name, false, false, false, false, false);
$channel->queue_bind($queue_name, $exchange_name);

$channel->basic_consume($queue_name, '', true, true, false, false, function ($message) use ($channel, $connection) {
    $event = json_decode($message->body, true);

    $channel->close();
    $connection->close();

    die(json_encode($event['event']));
});

while ($channel->is_open)
{
    $channel->wait();
}

$channel->close();
$connection->close();

?>