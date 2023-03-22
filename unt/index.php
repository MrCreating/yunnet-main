<?php

use unt\objects\Context;
use unt\objects\Project;

if (Context::get()->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] !== 'm')
{
    die(header("Location: ". Project::getMobileDomain() . $_SERVER['REQUEST_URI']));
}

//Session::start(1)->setAsCurrent();
if (!Context::get()->isLogged() && strtoupper($_SERVER['REQUEST_METHOD']) === "GET" && isset($_SESSION['stage']) && intval($_SESSION['stage']) > 2 && REQUESTED_PAGE !== "/register")
    die(header("Location: ". Project::getDefaultDomain() ."/register"));

$is_default_page = Project::isDefaultLink(REQUESTED_PAGE);
$selected_section = strtolower(basename(trim(substr(REQUESTED_PAGE, 1))));
if ($selected_section === '')
    $selected_section = Context::get()->isLogged() ? 'news' : 'login';

if (strtoupper($_SERVER['REQUEST_METHOD']) === "POST")
{
    session_write_close();

    if ($is_default_page)
    {
        if (file_exists(PROJECT_ROOT . '/unt/public/' . $selected_section . '.php'))
            require_once PROJECT_ROOT . '/unt/public/' . $selected_section . '.php';
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/wall")
    {
        require_once PROJECT_ROOT . '/unt/public/wall.php';
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 6) === "/photo")
    {
        require_once PROJECT_ROOT . '/unt/public/photo.php';
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/poll")
    {
        require_once PROJECT_ROOT . '/unt/public/poll.php';
    }

    require_once PROJECT_ROOT . '/unt/public/profile.php';

    die(json_encode(array('error' => 1)));
}

\unt\design\Template::get('head')->variables([
    'module'     => $selected_section,
    'is_default' => $is_default_page
])->show();
?>