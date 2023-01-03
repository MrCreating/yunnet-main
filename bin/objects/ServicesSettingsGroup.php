<?php

namespace unt\objects;

use unt\platform\DataBaseManager;
use Vodka2\VKAudioToken\AndroidCheckin;
use Vodka2\VKAudioToken\MTalkClient;
use Vodka2\VKAudioToken\TokenException;
use Vodka2\VKAudioToken\TokenReceiver;
use Vodka2\VKAudioToken\TwoFAHelper;

/**
 * Класс настроек для аккаунта
 */

class ServicesSettingsGroup extends SettingsGroup
{
    const VK_API_DOMAIN = 'https://api.vk.com/';

    const SERVICE_TYPE_UNT = 0;
    const SERVICE_TYPE_VK  = 1;

    public function __construct(Entity $entity, array $params)
    {
        parent::__construct($entity, Settings::SERVICES_GROUP, $params);
    }

    public function getServicesList (bool $receiveToken = false): array
    {
        $result = [
            [
                'type' => self::SERVICE_TYPE_VK,
                'bound' => false
            ]
        ];

        $tokens = [];

        $res = DataBaseManager::getConnection()->prepare('SELECT token FROM users.accounts WHERE owner_id = ? AND type = ? AND is_active = 1 LIMIT 1;');

        if ($res->execute([$this->entity->getId(), $result[0]['type']]))
        {
            $token = $res->fetch(\PDO::FETCH_ASSOC)['token'];
            if ($token)
            {
                if ($receiveToken)
                {
                    $tokens[] = $token;

                    return $tokens;
                }

                $result[0]['bound'] = true;

                $user_data = json_decode(file_get_contents(self::VK_API_DOMAIN . 'method/users.get?v=5.131&access_token=' . $token), true);
                if ($user_data['error'])
                {
                    $result[0]['bound'] = false;

                    if ($user_data['error']['error_code'] === 2 && $this->deleteService(self::SERVICE_TYPE_VK))
                    {
                        return $result;
                    }
                }

                $result[0]['first_name'] = $user_data['response'][0]['first_name'];
                $result[0]['last_name']  = $user_data['response'][0]['last_name'];
                $result[0]['user_id']    = $user_data['response'][0]['user_id'];
            }
        }

        return $result;
    }

    public function deleteService (int $type): bool
    {
        return DataBaseManager::getConnection()->prepare('UPDATE users.accounts SET is_active = 0 WHERE owner_id = ? AND type = ? LIMIT 1;')->execute([intval($_SESSION['user_id']), $type]);
    }

    /**
     * Добавляет сервис к текущей сущности
     * @param int $type - тип сервиса (1 - ВК)
     * @return int
     */
    public function addService (int $type, string $login = '', string $password = '', string $code = ''): int
    {
        if ($type === self::SERVICE_TYPE_VK)
        {
            if ($this->getServicesList()[0]['bound'])
            {
                return 1;
            }

            $params = new \Vodka2\VKAudioToken\CommonParams();
            $protoHelper = new \Vodka2\VKAudioToken\SmallProtobufHelper();

            $checkin = new AndroidCheckin($params, $protoHelper);
            $authData = $checkin->doCheckin();

            try {
                $mtalkClient = new MTalkClient($authData, $protoHelper);
                $mtalkClient->sendRequest();

                unset($authData['idStr']);
                $receiver = new TokenReceiver($login, $password, $authData, $params, $code);
                $token    = $receiver->getToken();

                $account_type = self::SERVICE_TYPE_VK;

                $res = DataBaseManager::getConnection()->prepare('INSERT INTO users.accounts (owner_id, type, token) VALUES (:owner_id, :type, :token);');

                $res->bindParam(':owner_id', $_SESSION['user_id'], \PDO::PARAM_INT);
                $res->bindParam(':type',     $account_type,\PDO::PARAM_INT);
                $res->bindParam(':token',    $token,\PDO::PARAM_STR);

                return (int) $res->execute();
            } catch (\Exception $e)
            {
                if ($e->getCode() === TokenException::TWOFA_REQ && isset($e->extra->validation_sid)) {
                    try {
                        (new TwoFAHelper($params))->validatePhone($e->extra->validation_sid);

                        return 10;
                    } catch (\Exception $e) {
                        if ($e->getCode() === 5)
                        {
                            return 20;
                        }
                    }
                }

                if ($e->getCode() === 2)
                {
                    return 5;
                }

                return 0;
            }

        }

        return 0;
    }

    public function toArray(): array
    {
        return [
            'services' => $this->getServicesList()
        ];
    }
}

?>