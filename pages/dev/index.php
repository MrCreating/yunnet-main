<?php

/**
 * API docs and JS server
*/

//die(http_response_code(404));

header('Access-Control-Allow-Origin: ' . unt\functions\get_page_origin());

$folder_name       = explode('/', strval(explode('?', $_SERVER['REQUEST_URI'])[0]))[1];
$file_name         = explode('/', strval(explode('?', $_SERVER['REQUEST_URI'])[0]))[2];

// gets the file path by cureent script path
$path = __DIR__ . '/ui/' . 
	$folder_name . '/' . 
	$file_name;

if (!file_exists($path) && !unt\functions\is_empty($folder_name))
{
	http_response_code(404);
	die('[]');
}

// get file.
$file = file_get_contents($path);
if ($file_name === 'management.js')
{
    if (getenv('UNT_PRODUCTION') == '1') {
        if (intval($_SESSION['user_id']) <= 0)
            $file = false;

        if (!class_exists('Entity'))
            require __DIR__ . '/../../bin/objects/Entity.php';

        $user = new User(intval($_SESSION['user_id']));
        if (!$user->valid() || $user->getAccessLevel() < 1)
            $file = false;
    }
}

if (!$file)
{
	die(require __DIR__ . "/../../dev/public/index.php");
}

session_write_close();

// setting file headers
header('Accept-Ranges: bytes');
header('Cache-Control: max-age=315360000');

// checking by extension and return it
switch (explode('.', explode('/', strval(explode('?', $_SERVER['REQUEST_URI'])[0]))[2])[1]) {
	case 'css':
		header('Content-Type: text/css;charset=utf8'); break;
	case 'js':
		header('Content-Type: text/javascript;charset=utf8'); break;
	default:
		$info = getimagesize($path);
		if ( $info )
		{
			$img = imagecreatefromstring(file_get_contents($path));
			$extension = image_type_to_extension($info[2]);

			// sending header with content type and image.
			header('Content-Type: image/'.substr($extension, 1));
			switch ($extension) {
				case '.png':
					die(imagepng($img));
				case '.gif':
					die(imagegif($img));
				case '.jpeg':
				case '.jpg':
					die(imagejpeg($img));
			}
        }
}

// send code
if ($file)
{
	die($file);
}

// if nothing found.
http_response_code(404);
die('[]');
?>