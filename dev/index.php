<?php

/**
 * API docs and JS server
*/

use unt\objects\Project;
use unt\platform\SourcesManager;

header('Access-Control-Allow-Origin: ' . Project::getOrigin());

$requested_path = explode('/', strval(explode('?', $_SERVER['REQUEST_URI'])[0]));

$folder_name = $requested_path[1];
$file_name = $requested_path[2];

// gets the file path by current script path
$path = '/dev/sources/ui/' . basename($folder_name) . '/' . basename($file_name);

$content = SourcesManager::load($path);

if ($content['data'] == '') {
    die(require_once __DIR__ . '/public/index.php');
}

if ($content['extension'] == 'js') {
    header('Content-Type: text/javascript;charset=utf8');
} else if ($content['extension'] == 'css') {
    header('Content-Type: text/css;charset=utf8');
} else {
   header('Content-Type: image/' . $content['extension']);
}

die($content['data']);

?>
