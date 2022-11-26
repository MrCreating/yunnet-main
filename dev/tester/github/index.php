<?php

$event = json_decode(file_get_contents('php://input'), true);

if ($event['ref'] !== "refs/heads/master")
{
    shell_exec('git pull && cd /home/unt/unt_2 && docker-compose restart php && cd /home/unt/');

    die(json_encode(array('response' => 1)));
}

die(json_encode(array('response' => 0)));
?>