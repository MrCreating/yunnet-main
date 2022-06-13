<?php

class AbstractAPIMethod
{
    protected string $methodName = '';

    protected int $methodPermissionsGroup = -1;

    // from request (parsed)
    protected array $params = [];

    // from method defines
    protected array $defaultParams = [];

    protected API $api;

    public ?APIException $error = NULL;

    // params from request!
    public function __construct(API $api, array $params = [])
    {
        $params = $this->parseParams($params);
        if ($params['error']) {
            $this->error = new APIException("Some parameters was missing or invalid: " . $params['error'] . " is invalid", -4);
            return;
        }

        $this->params = $params['result'];

        $this->api = $api;
    }

    public function getMethodName (): string
    {
        return $this->methodName;
    }

    // all objects needed from /objects/ folder!
    public function getNeededObjects (): array
    {
        return [];
    }

    public function parseParams (array $params): array
    {
        $resulted_params = [];
        $error_field     = false;

        foreach ($this->defaultParams as $name => $requirements)
        {
            if (intval($requirements['required']) && !isset($params[$name])) {
                $error_field = $name;
                break;
            }

            if (!isset($params[$name])) {
                if (isset($this->defaultParams[$name]) && isset($this->defaultParams[$name]['default_value'])) {
                    $resulted_params[$name] = $this->defaultParams[$name]['default_value'];
                } else continue;
            }

            switch ($this->defaultParams[$name]['type']) {
                case 'integer':
                    $resulted_params[$name] = intval($params[$name]);
                    break;
                case 'string':
                    $resulted_params[$name] = strval($params[$name]);
                    break;
                case 'json':
                    $json_resulted = json_decode($params[$name]);
                    if (!$json_resulted) {
                        $error_field = $name;
                        $resulted_params = [];
                        break;
                    }

                    $resulted_params[$name] = $json_resulted;
                    break;
                default:
                    $error_field = $name;
                    $resulted_params = [];
                    break;
            }
        }

        return [
            'result' => $resulted_params,
            'error'  => $error_field
        ];
    }

    public function getPermissionsGroup (): int
    {
        return $this->methodPermissionsGroup;
    }

    public function isPublicMethod (): bool
    {
        return $this->getPermissionsGroup() === -1;
    }

    public function run ()
    {}

    ///////////////////////////////
    public static function get (API $api, array $params)
    {
        if (!isset($_SERVER['calledAPIMethod']))
            return $_SERVER['calledAPIMethod'];

        return new self(
            $api, $params);
    }

    public static function findMethod (API $api, array $params = []): ?AbstractAPIMethod
    {
        $method_data = explode('.', explode('/', explode('?', $_SERVER['REQUEST_URI'])[0])[1], 2);

        if (count($method_data) < 2) return NULL;

        $method_group = strtolower($method_data[0]);
        $method_name  = strtolower($method_data[1]);

        $method_path = __DIR__ . '/../../api/methods/' . $method_group . '/' . $method_name . '.php';
        if (file_exists($method_path))
        {
            try {
                require_once $method_path;

                if (!class_exists('APIMethod')) return NULL;

                return new APIMethod($api, $params);
            } catch (Exception $e)
            {
                return NULL;
            }
        }

        return NULL;
    }
}