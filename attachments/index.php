<?php

/**
 * Attachments server.
*/

// closes the session.
session_write_close();

// sending the headers
header('Server: YunNet');
header("Access-Control-Allow-Credentials: true");

// functions which throws a not found error.
function return_not_found ()
{
	http_response_code(404);
	die('[]');
}

// we will get attachments path and another data from the database
$database_connection = DataBaseManager::getConnection();

// attachment query
$attachment_query = explode('__', substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI'])))[0];

// getting the attachment
$res = $database_connection->prepare('SELECT path FROM attachments.d_1 WHERE query = ? LIMIT 1;');
$res->execute([$attachment_query]);
$attachment_data = $res->fetch(PDO::FETCH_ASSOC);

// if not image registered.
if (!$attachment_data) return_not_found();

// getting the photo and return it.
$path = __DIR__ . '/' . $attachment_data['path'];
if (!file_exists($path))
	$path = __DIR__ . '/../' . $attachment_data['path'];

// here we will create image.

try {
	$img = new Imagick($path);
} catch (Exception $e) {
	return_not_found();
}
$len = filesize($path);

// if image not exists
if (!$img) return_not_found();

// getting image type and show it.
$type = '';
switch (explode('.', $path)[count(explode('.', $path))-1]) {
	case 'jpg':
		$type = 'jpeg';
	default:
		$type = explode('.', $path)[count(explode('.', $path))-1]; break;
};

// sending content type header.
header('Content-Type: image/'.$type.PHP_EOL);
header('Content-Length: '.$len);
header('Cache-Control: max-age=3600');

// show an a image
switch ($type) {
	case 'png':
		die(file_get_contents($path)); break;
	case 'gif':
		die(file_get_contents($path)); break;
	case 'jpg':
	case 'jpeg':
		die(file_get_contents($path)); break;
};
?>