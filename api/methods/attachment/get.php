<?php

class APIMethod extends AbstractAPIMethod
{
    protected string $methodName   = 'attachment.get';

    protected array $defaultParams = [
        'type'           => [
            'type'     => AbstractAPIMethod::PARAM_TYPE_STRING,
            'required' => 1
        ],
        'id'           => [
            'type'     => AbstractAPIMethod::PARAM_TYPE_INTEGER,
            'required' => 1
        ],
        'owner_id'     => [
            'type'     => AbstractAPIMethod::PARAM_TYPE_INTEGER,
            'required' => 1
        ],
        'access_key'   => [
            'type'     => AbstractAPIMethod::PARAM_TYPE_STRING,
            'required' => 1
        ]
    ];

    protected int $methodPermissionsGroup = 0;

    public function __construct(API $api, array $params = [])
    {
        parent::__construct($api, $params);
    }

    public function run(): APIResponse
    {
        $result     = [];

        $type       = $this->params['type'];
        $id         = $this->params['id'];
        $owner_id   = $this->params['owner_id'];
        $access_key = $this->params['access_key'];

        $attachment = (new AttachmentsParser())->getObject($type.$owner_id.'_'.$id.'_'.$access_key);
        if ($attachment)
        {
            $result = $attachment->toArray();
        }
        return new APIResponse(['response' => $result]);
    }
}