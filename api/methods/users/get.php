<?php

class APIMethod extends AbstractAPIMethod
{
    // user ids limit count
    private int $users_limit = 100;

    public function __construct(API $api, array $params = [])
    {
        $this->methodName = 'users.get';
        $this->methodPermissionsGroup = 0;
        $this->defaultParams = [
            'user_id' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_INTEGER,
                'required' => 0
            ],
            'user_ids' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_STRING,
                'required' => 0
            ],
            'fields' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_STRING,
                'required' => 0
            ]
        ];

        parent::__construct($api, $params);
    }

    public function run(): APIResponse
    {
        $user_ids = isset($this->params['user_ids']) ? array_unique(array_map(function ($item) {
            return intval($item);
        }, explode(',', $this->params['user_ids'], $this->users_limit))) : (
            isset($this->params['user_id']) ? [intval($this->params['user_id'])] : [intval($_SESSION['user_id'])]
        );

        $result = [];
        foreach ($user_ids as $user_id)
        {
            $user = Entity::findById($user_id);

            if ($user)
            {
                $result[] = $user->toArray(strval($this->params['fields']));
            }
        }

        return new APIResponse($result);
    }
}

?>