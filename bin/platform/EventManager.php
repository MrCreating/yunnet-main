<?php

namespace unt\platform;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use unt\objects\BaseObject;
use unt\objects\Bot;
use unt\objects\User;

class EventManager extends BaseObject
{
    protected int $entity_id;

    protected Cache $cache;

    public function __construct(int $entity_id)
    {
        parent::__construct();

        $this->entity_id = $entity_id;

        $this->cache = new Cache('event_sessions');
    }

    public function getEntityKey (): string
    {
        $entity_type = $this->entity_id > 0 ? User::ENTITY_TYPE : Bot::ENTITY_TYPE;

        return $entity_type . '_' . $this->entity_id;
    }

    public function createAuthKey (): string
    {
        $key_parts = [
            'qwirug_yhsd893768alihrgbfj2435145_knaew',
            $this->entity_id,
            'qwirug_yhsdnjk2563976768al_knaew',
        ];

        $resulted_key = substr(str_shuffle(implode('', $key_parts)), 0, 50);

        $this->cache->putItem($resulted_key, $this->getEntityKey(), 1800);
        $this->cache->putItem($resulted_key . '_active', '0', 1800);

        return $resulted_key;
    }

    public function deleteAuthKey (string $key): EventManager
    {
        $this->cache->removeItem($key);
        $this->cache->removeItem($key . '_active');

        return $this;
    }

    public function setKeyActive (string $key, bool $active): EventManager
    {
        $this->cache->putItem($key . '_active', strval(intval($active)));

        return $this;
    }

    ////////////////////////////////////////////
    public static function findByKey (string $key): ?EventManager
    {
        $cache = new Cache('event_sessions');

        $entity_data = $cache->getItem($key);
        if ($entity_data)
        {
            $entity_parts = explode('_', $entity_data, 2);

            $entity_type = $entity_parts[0];
            $entity_id   = $entity_parts[1];

            if ($entity_type === Bot::ENTITY_TYPE)
                $entity_id *= -1;

            return new static($entity_id);
        }

        return NULL;
    }

    public static function findByEntityId (int $entity_id): EventManager
    {
        return new static($entity_id);
    }

    public static function isKeyActive (string $key): bool
    {
        return boolval(intval((new Cache('event_sessions'))->getItem($key . '_active')));
    }

    /**
     * Посылает событие юзерам
     * @param array<int> $entities
     * @param array $event_data
     * @return bool
     */
    public static function event (array $entities, array $event_data): bool
    {
        try {
            $connection = new AMQPStreamConnection('rabbit_mq', 5672, getenv('RABBITMQ_DEFAULT_USER'), getenv('RABBITMQ_DEFAULT_PASS'));
            $channel = $connection->channel();

            $message = new AMQPMessage(json_encode([
                'metadata' => [],
                'event'    => $event_data,
            ]));

            foreach ($entities as $entity_id)
            {
                $entity_type = $entity_id > 0 ? User::ENTITY_TYPE : Bot::ENTITY_TYPE;

                $channel->exchange_declare($entity_type . '_' . $entity_id, 'fanout', false, false, false);
                $channel->basic_publish($message, $entity_type . '_' . $entity_id);
            }

            $channel->close();
            $connection->close();

            return true;
        } catch (\Exception $e)
        {
            return false;
        }
    }
}