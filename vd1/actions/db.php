<?php
function db (): PDO
{
    if (!isset($_SERVER['db']))
    {
        $_SERVER['db'] = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
            PDO::ATTR_PERSISTENT => false
        ]);
        $_SERVER['db']->prepare('SET NAMES utf8')->execute();
    }

    return $_SERVER['db'];
}
?>