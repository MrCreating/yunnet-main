<?php

require_once PROJECT_ROOT . '/pages/page_templates.php';

if (Context::get()->isMobile() && explode('.', strtolower($_SERVER['HTTP_HOST']))[0] !== 'm')
{
    die(header("Location: ". Project::getMobileDomain() . $_SERVER['REQUEST_URI']));
}

//Session::start(1)->setAsCurrent();
if (!Context::get()->isLogged() && strtoupper($_SERVER['REQUEST_METHOD']) === "GET" && isset($_SESSION['stage']) && intval($_SESSION['stage']) > 2 && REQUESTED_PAGE !== "/register")
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

        if (file_exists(PROJECT_ROOT . '/unt/public/'.$page[1].'.php'))
            require_once PROJECT_ROOT . '/unt/public/'.$page[1].'.php';
        else
            die(json_encode(array('error' => 1)));
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

die(default_page_template(Context::get()->isMobile(), Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));
?>