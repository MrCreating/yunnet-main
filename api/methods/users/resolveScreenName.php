<?php

class APIMethod extends AbstractAPIMethod
{
    protected string $methodName = 'users.resolveScreenName';

    protected array $defaultParams = [
        'screen_name' => [
            'type'     => AbstractAPIMethod::PARAM_TYPE_STRING,
            'required' => 1
        ]
    ];

    protected int $methodPermissionsGroup = 0;

    public function __construct(API $api, array $params = [])
    {
        parent::__construct($api, $params);
    }

    /**
     * @throws APIException
     */
    public function run(): APIResponse
    {
        $screen_name = $this->params['screen_name'];

        $user = User::findByScreenName($screen_name);
        if (!$user) {
            throw new APIException('Entity not found', 301);
        }

        return new APIResponse(['response' => [
            'entity_id'    => $user->getId(),
            'account_type' => $user->getType()
        ]]);
    }
}