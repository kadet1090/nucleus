<?php
/**
 * XMPP Library
 *
 * Copyright (C) 2016, Some right reserved.
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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait Logging
{
    private static $global = null;

    /** @var LoggerInterface */
    private $_logger = null;

    public static function setGlobalLogger(LoggerInterface $logger)
    {
        self::$global = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger() : LoggerInterface
    {
        if ($this->_logger === null) {
            $this->_logger = self::getGlobalLogger();
        }

        return $this->_logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public static function getGlobalLogger()
    {
        if (self::$global === null) {
            self::$global = new NullLogger();
        }

        return self::$global;
    }
}
