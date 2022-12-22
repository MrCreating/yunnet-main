<?php
function db (): PDO
{
    if (!isset($_SERVER['db']))
    {
        if (\unt\objects\Project::isProduction())
            $_SERVER['db'] = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
                PDO::ATTR_PERSISTENT => false
            ]);
        else
            $_SERVER['db'] = new PDO("mysql:host=host.docker.internal", 'root', 'pC2022_DiE', [
                PDO::ATTR_PERSISTENT => false
            ]);

        $_SERVER['db']->prepare('SET NAMES utf8')->execute();
    }

    return $_SERVER['db'];
}
?>