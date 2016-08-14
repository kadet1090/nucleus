<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2016, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Exception\Protocol;

use Exception;
use Kadet\Xmpp\Exception\ProtocolException;
use Kadet\Xmpp\Stream\Error;

class StreamErrorException extends ProtocolException
{
    /**
     * @var Error
     */
    private $_error;

    public function getError() : Error
    {
        return $this->_error;
    }

    public function __construct(Error $error, $message = null, $code = 0, Exception $previous = null)
    {
        $this->_error = $error;
        parent::__construct($message ?: $this->generateMessage($error), $code, $previous);
    }

    private function generateMessage(Error $error) : string
    {
        return 'Stream error: '.str_replace('-', ' ', $error->kind).($error->text ? " ({$error->text})" : null);
    }
}
