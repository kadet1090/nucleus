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
    private $logger = null;

    public static function set(LoggerInterface $logger)
    {
        self::$global = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = self::get();
        }

        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public static function get()
    {
        if (self::$global === null) {
            self::$global = new NullLogger();
        }

        return self::$global;
    }
}
