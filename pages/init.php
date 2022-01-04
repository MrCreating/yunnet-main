<?php

require_once __DIR__ . '/page_templates.php';

/**
 * creates an a context and returns html of selected page.
 */

// setting the header
header('Save-Data: on');
header('Strict-Transport-Security: max-age=31536000; preload; includeSubDomains');

$context = Context::get();
$connection = $context->getConnection();

/*if (!$context->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] === 'm')
{
	die(header("Location: ". Project::getDefaultDomain() . $_SERVER['REQUEST_URI']));
}*/
if ($context->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] !== 'm')
{
	die(header("Location: ". Project::getMobileDomain() . $_SERVER['REQUEST_URI']));
}

//Session::start(1)->setAsCurrent();
if (!$context->isLogged() && strtoupper($_SERVER['REQUEST_METHOD']) === "GET" && isset($_SESSION['stage']) && intval($_SESSION['stage']) > 2 && REQUESTED_PAGE !== "/register")
	die(header("Location: ". Project::getDefaultDomain() ."/register"));

if (strtoupper($_SERVER['REQUEST_METHOD']) === "POST")
{
	if (Project::isDefaultLink(REQUESTED_PAGE))
	{
		session_write_close();
		$page = explode('/', REQUESTED_PAGE);

		if ($page[1] === "" && $page[0] === "") {
			$page[1] = 'news';
		}

		if (file_exists(__DIR__ . '/public/'.$page[1].'.php'))
			require_once __DIR__ . '/public/'.$page[1].'.php';
		else
			die(json_encode(array('error' => 1)));
	}

	if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/wall")
	{
		require_once __DIR__ . '/public/wall.php';
	}

	if (substr(strtolower(REQUESTED_PAGE), 0, 6) === "/photo")
	{
		require_once __DIR__ . '/public/photo.php';
	}

	require_once __DIR__ . '/public/profile.php';

	die(json_encode(array('error' => 1)));
}

die(default_page_template($context->isMobile(), $context->getLanguage()->id, $context->getCurrentUser()));
?>