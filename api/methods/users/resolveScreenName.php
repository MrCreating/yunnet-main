<?php

class APIMethod extends AbstractAPIMethod
{
    protected array $defaultParams = [
        'screen_name' => [
            'type'=> AbstractAPIMethod::PARAM_TYPE_STRING,
            'required' => 1
        ]
    ];

    protected int $methodPermissionsGroup = 0;

    protected string $methodName = 'users.resolveScreenName';


    public function __construct(API $api, array $params = [])
    {
        parent::__construct($api, $params);
    }

    public function run(): APIResponse
    {
        $result = [];

        $screen_name = $this->params['screen_name'];

        $user = User::findByScreenName($screen_name);

        if($user) {
            $result = [
                'entity_id' => $user->getId(),
                'account_type' => $user->getType()
            ];
        }


        return new APIResponse(['response' => $result]);
    }
}


