<?php
/**
 * creates an a context and returns html of selected page.
*/
//ini_set("display_errors", 1);

// setting the header
header('Save-Data: on');
header('Strict-Transport-Security: max-age=31536000; preload; includeSubDomains');

if (!class_exists('Context'))
	require __DIR__ . '/../bin/context.php';

$context = new Context();
$connection = $context->getConnection();

if ($context->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] !== 'm')
	die(header("Location: ".DEFAULT_MOBILE_URL.$_SERVER['REQUEST_URI']));
if (!$context->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] === 'm')
	die(header("Location: ".DEFAULT_URL.$_SERVER['REQUEST_URI']));

//Session::start(1)->setAsCurrent();

if (strtoupper($_SERVER['REQUEST_METHOD']) === "POST")
{
	if (in_array(REQUESTED_PAGE, get_default_pages()))
	{
		session_write_close();
		$page = explode('/', REQUESTED_PAGE);

		if ($page[1] === "" && $page[0] === "") {
			$page[1] = 'news';
		}

		if (file_exists(__DIR__ . '/public/'.$page[1].'.php'))
			require __DIR__ . '/public/'.$page[1].'.php';
		else
			die(json_encode(array('error' => 1)));
	}

	if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/wall")
	{
		require __DIR__ . '/public/wall.php';
	}

	if (substr(strtolower(REQUESTED_PAGE), 0, 6) === "/photo")
	{
		require __DIR__ . '/public/photo.php';
	}

	require __DIR__ . '/public/profile.php';

	die(json_encode(array('error' => 1)));
}

require __DIR__ . '/page_templates.php';

die(default_page_template($context->isMobile(), $context->getLanguage()->id, $context->getCurrentUser()));
?>