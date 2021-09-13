<?php

/**
 * Here is the themes server.
*/

// handle selected theme
$selected_theme = explode('/', explode('?', strtolower($_SERVER["REQUEST_URI"]))[0])[1];
if ($selected_theme === "")
{
	header("Content-Type: application/json"); http_response_code(404); die('[]');
}

// getting database connection and require Theme object file.
if (!class_exists('Theme'))
	require __DIR__ ."/../bin/objects/theme.php";
if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/../bin/objects/attachment.php';

require __DIR__ ."/../bin/functions/users.php";

// here we will setup headers for CORS
header('Access-Control-Allow-Origin: '.get_page_origin());
header('Access-Control-Allow-Credentials: true');    

// getting DB connection and setup theme.
$connection = get_database_connection();
$theme      = (new AttachmentsParser())->getObject($selected_theme);

if (!$theme || ($theme->isPrivate() && $theme->getOwner()->getId() !== intval($_SESSION['user_id']) && !$theme->isDefault()))
{
	header("Content-Type: application/json"); http_response_code(404); die('[]');
}

// theme instance created. Now we can parse params
$params  = explode('&', explode('?', strtolower($_SERVER["REQUEST_URI"]))[1]);
$request = [];
foreach ($params as $index => $item) {
	$data = explode('=', $item);
	$request[$data[0]] = $data[1];
}

// setting up theme by mode.
$mode = strtolower($request["mode"]);
switch ($mode) {
	case 'export':
		if ($theme->getOwner()->getId() !== intval($_SESSION['user_id']))
			die(json_encode(array('error' => array('error_code' => 302, 'error_message' => 'You do not have access to import this theme'))));

		$fileData = $theme->createUTH();

		header('Content-Description: File Transfer');
    	header('Content-Type: application/octet-stream');
    	header('Content-Disposition: attachment; filename='.$theme->getCredentials().'.uth');
    	header('Content-Transfer-Encoding: binary');
    	header('Expires: 86400');
    	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
   		header('Pragma: public');
   		header('Content-Length: '.($fileData->length()));

   		die($fileData->build());
	break;
	case 'css':
		$code = $theme->getCSSCode();
		if (!$code)
		{
			header("Content-Type: application/json");
			die(json_encode(array('error' => array('error_code' => 300, 'error_message' => 'Code for this param is not found'))));
		}

		// send code.
		header("Content-Type: text/css");
		header("Access-Control-Allow-Origin: ".get_page_origin());
		die($code);
	break;
	case 'js':

		// checking js code evaluation allowance
		$allow_state = is_js_allowed($connection, $_SESSION["user_id"]);
		if (!$allow_state)
		{
			header("Content-Type: application/json");
			die(json_encode(array('error' => array('error_code'=> 301, 'error_message' => 'You do not have access to this param by your privacy settings'))));
		}

		// if OK getting js code.
		$code = $theme->getJSCode();
		if (!$code)
		{
			header("Content-Type: application/json");
			die(json_encode(array('error' => array('error_code' => 300, 'error_message' => 'Code for this param is not found'))));
		}

		// send code.
		header("Content-Type: text/javascript");
		header("Access-Control-Allow-Origin: ".get_page_origin());
		die($code);
	break;
	default:
		header("Content-Type: application/json"); 
		die(json_encode($theme->toArray()));
	break;
}

// by default - not found.
header("Content-Type: application/json"); 
die('[]');
?>