<?php

namespace unt\parsers;

use unt\objects\BaseObject;
use unt\objects\Context;
use unt\platform\Data;

class StringHelper extends BaseObject
{
    public static function online (?Data $online, $gender = 0): string
    {
        if (!$online) {
            return Context::get()->getLanguage()->bot;
        }

        if ($online->isOnlineHidden)
            return Context::get()->getLanguage()->onlineHidden;

        if ($online->isOnline)
            return Context::get()->getLanguage()->online;

        if ($online->lastOnlineTime > 0) {

            // 1 - мальчик
            // 2 - девочка
            if (Context::get()->getLanguage()->id === 'ru')
            {
                return mb_strtolower(($gender === 1 ? (Context::get()->getLanguage()->was) : (Context::get()->getLanguage()->was . 'а')) . ' ' . (Context::get()->getLanguage()->online) . ' ' . self::unix_to_time($online->lastOnlineTime));
            }
            else
            {
                return mb_strtolower(Context::get()->getLanguage()->was . ' ' . (Context::get()->getLanguage()->online) . ' ' . self::unix_to_time($online->lastOnlineTime));
            }
        }

        return Context::get()->getLanguage()->offline;
    }

    public static function unix_to_time (int $unix_time): string
    {
        return date('d.m.Y, H:i', $unix_time);
    }
}