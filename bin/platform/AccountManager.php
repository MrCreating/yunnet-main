<?php

namespace unt\platform;

use unt\exceptions\EntityNotFoundException;
use unt\objects\BaseObject;
use unt\objects\Bot;
use unt\objects\Entity;
use unt\objects\User;

class AccountManager extends BaseObject
{
    protected Entity $entity;

    /**
     * @throws EntityNotFoundException
     */
    public function __construct(int $entity_id)
    {
        parent::__construct();

        $entity = Entity::findById($entity_id);
        if (!$entity)
            throw new EntityNotFoundException('Can not found entity');

        $this->entity = $entity;
    }

    public function getEntity (): Entity
    {
        return $this->entity;
    }

    public function ban (): bool
    {
        $entity_id = $this->getEntity()->getType() === User::ENTITY_TYPE ? $this->getEntity()->getId() : ($this->getEntity()->getId() * -1);

        $is_banned = $this->getEntity()->isBanned();

        return DataBaseManager::getConnection()->prepare("UPDATE ".(intval($entity_id) < 0 ? "bots.info" : "users.info")." SET is_banned = ? WHERE id = ? AND is_deleted = 0 LIMIT 1;")->execute([intval(!$is_banned), $this->getEntity()->getId()]);
    }

    public function verify (): bool
    {
        return intval(DataBaseManager::getConnection()->prepare("UPDATE ".($this->getEntity()->getType() === Bot::ENTITY_TYPE ? "bots.info" : "users.info")." SET is_verified = ? WHERE id = ? LIMIT 1;")->execute([intval(!intval($this->getEntity()->isVerified())), $this->getEntity()->getId()]));
    }

    public function toggleOnlineHidden (): bool
    {
        return intval(DataBaseManager::getConnection()->prepare("UPDATE users.info SET online_hidden = ? WHERE id = ? LIMIT 1;")->execute([intval(!intval($this->getEntity()->getOnline()->isOnlineHidden)), $this->getEntity()->getId()]));
    }

    public function deleteEntity (): bool
    {
        $entity_id = $this->getEntity()->getType() === User::ENTITY_TYPE ? $this->getEntity()->getId() : ($this->getEntity()->getId() * -1);

        $res = DataBaseManager::getConnection()->prepare("UPDATE " . ($entity_id > 0 ? "users.info" : "bots.info") . " SET is_deleted = 1 WHERE id = ? LIMIT 1");

        if ($res->execute([$entity_id > 0 ? $entity_id : ($entity_id * -1)]))
        {
            return true;
        }

        return false;
    }

    //////////////////////////////

    /**
     * @throws EntityNotFoundException
     */
    public static function create (int $entity_id): AccountManager
    {
        return new self($entity_id);
    }
}