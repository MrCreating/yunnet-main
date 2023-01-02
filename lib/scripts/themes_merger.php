<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO("mysql:host=mysql", getenv('MYSQL_ROOT_USER'), getenv('MYSQL_ROOT_PASSWORD'), [
    PDO::ATTR_PERSISTENT => false
]);

$res = $db->prepare('SELECT id, owner_id, path_to_js, path_to_css FROM users.themes');

$data = $res->execute();

$themes = $res->fetchAll(PDO::FETCH_ASSOC);

foreach ($themes as $theme)
{
    $theme_css_path = __DIR__ . '/../../attachments/themes' . $theme['path_to_css'];
    $theme_js_path = __DIR__ . '/../../attachments/themes' . $theme['path_to_js'];

    if (!$theme['path_to_css']) continue;
    if (!$theme['path_to_js']) continue;

    if (!file_exists($theme_js_path) && !file_exists($theme_css_path))
        continue;

    $css_data = file_get_contents($theme_css_path);
    $js_data  = file_get_contents($theme_js_path);

    echo 'Working with theme ' . $theme['id'] . ' and ' . $theme['owner_id'] . '...' . PHP_EOL;
}
?>