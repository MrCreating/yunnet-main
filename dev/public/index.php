<?php
/**
 * Start point of dev platform!!!
*/

if (!class_exists('Context'))
	require __DIR__ . '/../../bin/Context.php';

if (!class_exists('AttachmentsParser'))
	require __DIR__ . '/../../bin/objects/Attachment.php';

if (!class_exists('Entity'))
	require __DIR__ . '/../../bin/objects/entities.php';

if (!function_exists('get_registered_methods'))
	require __DIR__ . '/../../bin/functions/dev_functions.php';

// variables
$is_logged = Context::get()->isLogged();
$is_mobile = Context::get()->isMobile();

if (strtoupper($_SERVER['REQUEST_METHOD']) === "POST")
{
	$requested_page = explode('/', $requested_page)[1];
	if ($requested_page === "")
		$requested_page = "main";

	require __DIR__ . '/' . $requested_page . '.php';

	die(json_encode(array('error' => 1)));
}

require __DIR__ . '/../../pages/page_templates.php';
die(default_page_template($is_mobile, Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));
?>