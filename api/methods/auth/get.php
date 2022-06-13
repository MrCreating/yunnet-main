<?php

class APIMethod extends AbstractAPIMethod
{
    public function __construct(API $api, array $params = [])
    {
        $this->methodName = 'auth.get';
        $this->methodPermissionsGroup = -1;

        $this->defaultParams = [
            'login' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_STRING,
                'required' => 1
            ],
            'password' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_STRING,
                'required' => 1
            ],
            'app_id' => [
                'type' => AbstractAPIMethod::PARAM_TYPE_INTEGER,
                'required' => 1
            ]
        ];

        parent::__construct($api, $params);
    }

    public function getNeededObjects(): array
    {
        return [
            'app'
        ];
    }

    /**
     * @throws APIException
     */
    public function run()
    {
        $login = $this->params['login'];
        $password = $this->params['password'];
        $app_id = $this->params['app_id'];

        $app = new App($app_id);

        if (!$app->valid())
            throw new APIException("App is not found", 101);
        if (!$app->isDirectAuthAllowed())
            throw new APIException("Direct auth is not enabled in this app", 102);

        $entity = User::auth($login, $password);
        if (!$entity)
            throw new APIException("Failed to auth: login or password is incorrect", -1);

        if ($entity->isBanned())
            throw new APIException("The account is banned", -30);

        $token = $app->createToken([1, 2, 3, 4]);
        if (!$token || !$token->valid())
            throw new APIException("Failed to auth: internal error", 102);

        return new APIResponse([
            'user_id'   => $entity->getId(),
            'user_data' => $entity->toArray('*'),
            'token'     => $token->getToken(),
            'token_id'  => $token->getId()
        ]);
    }
}