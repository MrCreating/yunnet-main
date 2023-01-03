<?php

namespace unt;

use unt\objects\Request;

/**
 * Класс ядра
 */
class UntEngine
{
    protected array $subdomains = [
        'm', 'api', 'dev', 'd-1', 'yunnet', 'events', 'themes', 'auth', 'test', 'vd'
    ];

    public function __construct ()
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/../lib/vk_audio/autoloader.php';
        require_once __DIR__ . '/base_functions.php';

        if (getenv('UNT_PRODUCTION') !== '1')
        {
            session_write_close();
            ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']);
            session_start();
        }

        header('Save-Data: on');
        header('Strict-Transport-Security: max-age=31536000; preload; includeSubDomains');

        spl_autoload_register(function ($data) {
            $path_templates = explode('\\', $data);

            $path = __DIR__ . '/' . $path_templates[1] . '/' . $path_templates[2] . '.php';
            if (count($path_templates) < 2)
            {
                $path = __DIR__ . '/objects/' . $path_templates[0] . '.php';
                if (!file_exists($path))
                    $path = __DIR__ . '/platform/' . $path_templates[0] . '.php';
                if (!file_exists($path))
                    $path = __DIR__ . '/parsers/' . $path_templates[0] . '.php';
            }

            include_once $path;
        });

        $to             = explode('.', strtolower($_SERVER['HTTP_HOST']))[0];
        $requested_page = explode('?', strtolower($_SERVER['REQUEST_URI']))[0];

        if (!in_array($to, $this->subdomains))
        {
            //die(header("Location: " . getenv('UNT_PRODUCTION' === '1' ? 'https://yunnet.ru/' : 'http://localhost')));
        }

        // constants
        define('REQUESTED_PAGE', $requested_page);
        define('PROJECT_ROOT', __DIR__ . '/..');

        // checking domains.
        switch ($to)
        {
            case "api":
                // https://api.yunnet.ru - API для разработчиков
                die(require_once __DIR__ . '/../api/index.php');
            case "dev":
                // https://dev.yunnet.ru - доки для разработчиков
                die(require_once __DIR__ . '/../dev/index.php');
            case "d-1":
                // https://d-1.yunnet.ru - сервер вложений
                die(require_once __DIR__ . '/../attachments/index.php');
            case 'events':
                // https://events.yunnet.ru - сервер событий
                die(require_once __DIR__ . '/../events/index.php');
            case "themes":
                // https://themes.yunnet.ru - сервер тем
                die(require_once __DIR__ . '/../themes/index.php');
            case "auth":
                // https://auth.yunnet.ru - виджет OAuth
                die(require_once __DIR__ . '/../pages/widgets/auth/index.php');
            case "test":
                // https://test.yunnet.ru - GitHub WenHook панель управления + обработка событий разработки
                die(require_once __DIR__ . '/../test/index.php');
        }

        // https://yunnet.ru все остальное
        die(require_once __DIR__ . '/../unt/index.php');
    }

    /**
     * Быстрое включение или выключение ошибок
     */
    public function errors (bool $enable = true): UntEngine
    {
        ini_set('display_errors', $enable);
        error_reporting($enable ? E_ALL : 0);
        return $this;
    }

    public static function init (): UntEngine
    {
        return new static();
    }
}