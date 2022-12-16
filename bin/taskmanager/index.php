<?php

/**
 * This is the monitor of errors
*/
register_shutdown_function("handle_fatal_errors");

function handle_fatal_errors ()
{
	$error = error_get_last();
}

function handle_api_fatal_errors ()
{
	$error = error_get_last();
	if ($error['type'] !== 64)
		return;

	$message_text = '
[ALERT SYSTEM]

Got Fatal Error: ' . $error['type'] . '
======================================
File: ' . $error['file'] . '
Line: ' . $error['line'] . '
======================================

Text message: **' . $error['message'] . '**
';
	
	die(create_json_error(-100, 'Internal server error'));

	/*send_message($_SERVER['dbConnection'], get_uid_by_lid($_SERVER['dbConnection'], 1, false, 1), 1, ['chat_id' => 1, 'is_bot' => false], [
		'text' => $message_text,
		'attachments' => '',
		'fwd' => ''
	]);*/	
}

?>