<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2017, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Utils;


class Enum
{
    public static function available() {
        static $reflection = null;
        if(!$reflection) {
            $reflection = new \ReflectionClass(self::class);
        }

        return $reflection->getConstants();
    }

    public static function valid($value) {
        return in_array($value, self::available());
    }
}