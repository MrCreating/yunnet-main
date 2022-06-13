<?php

class APIMethod extends AbstractAPIMethod
{
    public function __construct(API $api, array $params = [])
    {
        $this->methodName = 'settings.get';
        $this->methodPermissionsGroup = 4;
        $this->defaultParams = [];

        parent::__construct($api, $params);
    }

    public function run()
    {
        $settings = $this->api->getOwner()->getSettings()->toArray();

        return new APIResponse($settings);
    }
}