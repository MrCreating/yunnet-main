<?php

namespace unt\objects;

use unt\platform\DataBaseManager;

/**
 * Базовый объект ядра.
 */
class BaseObject
{
    protected DataBaseManager $currentConnection;

    public function __construct()
    {
        $this->currentConnection = DataBaseManager::getConnection();
    }
}