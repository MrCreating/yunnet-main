<?php

require_once PROJECT_ROOT . '/pages/page_templates.php';

$context = Context::get();
$connection = $context->getConnection();

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

        if (file_exists(PROJECT_ROOT . '/pages/public/'.$page[1].'.php'))
            require_once PROJECT_ROOT . '/pages/public/'.$page[1].'.php';
        else
            die(json_encode(array('error' => 1)));
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/wall")
    {
        require_once PROJECT_ROOT . '/pages/public/wall.php';
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 6) === "/photo")
    {
        require_once PROJECT_ROOT . '/pages/public/photo.php';
    }

    if (substr(strtolower(REQUESTED_PAGE), 0, 5) === "/poll")
    {
        require_once PROJECT_ROOT . '/pages/public/poll.php';
    }

    require_once PROJECT_ROOT . '/pages/public/profile.php';

    die(json_encode(array('error' => 1)));
}

die(default_page_template($context->isMobile(), $context->getLanguage()->id, $context->getCurrentUser()));
?>