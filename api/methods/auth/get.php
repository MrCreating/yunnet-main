<?php

class APIMethod extends AbstractAPIMethod
{
    public function __construct(API $api, array $params = [])
    {
        $this->methodName = 'auth.get';
        $this->methodPermissionsGroup = -1;

        $this->defaultParams = [
            'login' => [
                'type' => 'string',
                'required' => 1
            ],
            'password' => [
                'type' => 'string',
                'required' => 1
            ]
        ];

        parent::__construct($api, $params);
    }

    public function run()
    {

    }
}