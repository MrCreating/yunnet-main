<?php
function db (): PDO
{
    if (!isset($_SERVER['db']))
    {
        $_SERVER['db'] = new PDO("mysql:host=host.docker.internal", 'root', 'pC2022_DiE', [
            PDO::ATTR_PERSISTENT => false
        ]);
        $_SERVER['db']->prepare('SET NAMES utf8')->execute();
    }

    return $_SERVER['db'];
}
?>