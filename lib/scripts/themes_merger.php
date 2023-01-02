<?php

$db = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
    PDO::ATTR_PERSISTENT => false
]);

$res = $db->prepare('SELECT id, owner_id, js_path, css_path FROM users.themes');

$data = $res->execute();

$themes = $res->fetchAll(PDO::FETCH_ASSOC);

foreach ($themes as $theme)
{
    var_dump($theme);
}
?>