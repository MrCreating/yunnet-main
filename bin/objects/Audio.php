<?php

namespace unt\objects;

use Vodka2\VKAudioToken\SupportedClients;

class Audio extends Attachment
{
    public function __construct(int $id, int $owner_id, string $access_key)
    {
        parent::__construct();
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType()
        ];
    }

    public function getCredentials(): string
    {
        return $this->getType();
    }

    public function getType(): string
    {
        return self::ATTACHMENT_TYPE;
    }

    /////////////////////////////////////////
    public const ATTACHMENT_TYPE = 'audio';

    public static function getList (int $offset = 0, int $count = 30): array
    {
        return [];
    }

    public static function getListFromService (int $service_type = ServicesSettingsGroup::SERVICE_TYPE_VK, int $offset = 0, int $count = 30): array
    {
        $result = [];

        if ($service_type === ServicesSettingsGroup::SERVICE_TYPE_UNT)
        {
            return self::getList($offset, $count);
        }

        if ($service_type === ServicesSettingsGroup::SERVICE_TYPE_VK)
        {
            $token = Context::get()
                ->getCurrentUser()
                ->getSettings()
                ->getSettingsGroup(\unt\objects\Settings::SERVICES_GROUP)
                ->getServicesList(true)[0];

            if ($token)
            {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: ' . SupportedClients::Kate()->getUserAgent()));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, "https://api.vk.com/method/audio.get?access_token=".$token."&count=".intval($count)."&offset=".intval($offset)."&v=5.95");

                $audios = json_decode(curl_exec($ch), true)['response']['items'];
                curl_close($ch);

                if ($audios)
                {
                    foreach ($audios as $index => $audio)
                    {
                        $audioObject = [
                            'owner_id'   => null,
                            'id'         => null,
                            'access_key' => null,
                            'url'        => $audio['url'],
                            'title'      => $audio['title'],
                            'artist'     => $audio['artist'],
                            'lyrics'     => '',
                            'duration'   => $audio['duration'],
                            'service'    => [
                                'internal_credentials' => 'audio' . $audio['owner_id'] . '_' . $audio['id'] . '_' . $audio['access_key']
                            ]
                        ];

                        $result[] = $audioObject;
                    }

                    return $result;
                }
            }
        }

        return $result;
    }
}