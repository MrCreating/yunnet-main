<?php
header("Content-Type: application/json");

session_write_close();
parse_str(explode("?", $_SERVER["REQUEST_URI"])[1], $_REQUEST);
$_REQUEST = array_merge($_REQUEST, $_POST);

/**
 * API server.
*/
require __DIR__ . '/../bin/functions/dev_functions.php';
require __DIR__ . '/../bin/taskmanager/index.php';

register_shutdown_function("handle_api_fatal_errors");

// temporality API service will be close
//die(create_json_error(0, 'API service temporally unavailable'));

// Request method must be GET or POST
if (strtolower($_SERVER["REQUEST_METHOD"]) !== "get" && strtolower($_SERVER["REQUEST_METHOD"]) !== "post")
{
	header("Access-Control-Allow-Origin: *");
	die(create_json_error(0, 'Request method must be GET or POST'));
}

$method_requested = strval($_REQUEST["method"]) === "" ? (explode("?", explode("/", strval($_SERVER["REQUEST_URI"]))[1])[0]) : (strval($_REQUEST["method"]));
$key = strval($_REQUEST["key"]);

// now we can do auth.
require __DIR__ . "/../bin/functions/auth.php";

$auth       = strtolower($_REQUEST["auth"]);
$connection = get_database_connection();

// parsing method name and groupd
$method_data  = explode('.', $method_requested);
$method_group = basename($method_data[0]);
$method_name  = basename($method_data[1]);

$free_groups = ['auth'];

$context = NULL;
if ($auth === "local")
{
	header('Access-Control-Allow-Origin:      https://dev.yunnet.ru');
	header('Access-Control-Allow-Credentials: true');

	if (!isset($_SESSION['user_id']))
		die(create_json_error(-1, 'Authentication failed: local auth is unavailable'));

	$context = [
		'user_id'      => intval($_SESSION['user_id']),
		'permissions'  => ['1', '2', '3', '4'],
		'owner_object' => intval($_SESSION['user_id']) > 0 ? new User(intval($_SESSION['user_id'])) : new Bot(intval($_SESSION['user_id'])*-1)
	];
} else
{
	header("Access-Control-Allow-Origin: *");
	if (!in_array($method_group, $free_groups))
	{
		$context = auth_by_token($connection, $_REQUEST["key"]);
		if (!$context)
		{
			die(create_json_error(-1, 'Authentication failed: incorrect access key'));
		}

		if (intval($context['owner_object']->profile['is_banned']))
		{
			die(create_json_error(-30, 'Authentication failed: account is banned'));
		}
	}
}

$_SERVER['dbConnection'] = $connection;
$_SESSION['user_id'] = intval($context['user_id']);

$registered_methods = get_registered_methods();

if (!isset($registered_methods[$method_group]) || !in_array($method_name, $registered_methods[$method_group]))
{
	die(create_json_error(1, 'Unknown method passed'));
}

/**
$method_groups = [
	'account',  'chats', 'friends',
	'messages', 'news',  'realtime',
	'uploads',  'users', 'wall',
	'notificatons', 'auth'
];

// Setting up groups and permissions
$permissions = $context["permissions"];
$friends_group = [
	$method_groups[2], $method_groups[8]
];
$messages_groups = [
	$method_groups[1], $method_groups[3]
];
$settings_groups = [
	$method_groups[5], $method_groups[6] 
];
$manage_groups = [
	$method_groups[0]
];
*/

try 
{
	die(require __DIR__ . '/methods/' . $method_group . '/' . $method_name . '.php');
} catch (Exception $error)
{
	die(create_json_error(-100, 'Internal server error'));
}

die(create_json_error(-100, 'Internal server error'));
?>