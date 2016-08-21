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
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Error;

class BindingException extends StanzaException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @param Jid       $jid
     * @param Error     $error
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     * @return static
     * @since    5.1.0
     */
    public static function fromError(Jid $jid, Error $error, Exception $previous = null)
    {
        return new static($error, \Kadet\Xmpp\Utils\helper\format("Cannot bind {resource} for {bare}. {condition}", [
            'resource'  => $jid->resource ?: "no resource",
            'bare'      => (string)$jid->bare(),
            'condition' => static::_conditionDescription($error)
        ]), 0, $previous);
    }

    private static function _conditionDescription(Error $error)
    {
        if(!empty($error->text)) {
            return $error->text;
        }

        return [
            'bad-request' => 'Bad Request: "{resource}" is not valid XMPP resource identifier.',
            'conflict'    => 'Conflict: {bare}/{resource} is already in use.'
        ][$error->condition] ?? 'Unknown reason: '.$error->condition;
    }
}
