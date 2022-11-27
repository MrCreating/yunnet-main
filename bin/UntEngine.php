<?php

namespace unt;

class UntEngine
{
    protected array $subdomains = [
        'm', 'api', 'dev', 'd-1', 'yunnet', 'lp', 'themes', 'auth', 'test'
    ];

    public function __construct ()
    {
        spl_autoload_register(function ($data) {
            $path_templates = explode('\\', $data);

            $path = __DIR__ . '/' . $path_templates[1] . '/' . $path_templates[2] . '.php';

            require_once $path;
        });

        $to             = explode('.', strtolower($_SERVER['HTTP_HOST']))[0];
        $requested_page = explode('?', strtolower($_SERVER['REQUEST_URI']))[0];

        require_once __DIR__ . '/base_functions.php';

        // checking domains.
        switch ($to)
        {
            case "api":
                die(require_once __DIR__ . '/../api/index.php');
            case "dev":
                die(require_once __DIR__ . '/../pages/dev/index.php');
            case "d-1":
                die(require_once __DIR__ . '/../attachments/index.php');
            case "lp":
                die(require_once __DIR__ . '/../pages/lp/index.php');
            case "themes":
                die(require_once __DIR__ . '/../attachments/themes.php');
            case "auth":
                die(require_once __DIR__ . '/../pages/widgets/auth/index.php');
            case "test":
                die(require_once __DIR__ . '/../dev/tester/index.php');
        }

        die(require_once __DIR__ . '/../pages/init.php');
    }

    public static function init (): UntEngine
    {
        return new static();
    }
}