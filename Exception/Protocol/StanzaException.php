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
use Kadet\Xmpp\Exception\PropertyAccessException;
use Kadet\Xmpp\Stanza\Error;

class StanzaException extends PropertyAccessException
{
    /** @var Error */
    private $_error;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link  http://php.net/manual/en/exception.construct.php
     * @param Error     $error    Stanza error describing exception
     * @param string    $message  [optional] The Exception message to throw.
     * @param int       $code     [optional] The Exception code.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     * @since 5.1.0
     */
    public function __construct(Error $error, $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->_error = $error;
    }


    /**
     * @return Error
     */
    public function getError() : Error
    {
        return $this->_error;
    }
}
