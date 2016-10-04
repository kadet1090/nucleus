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

namespace Kadet\Xmpp\Sasl;


class DigestMD5 extends \Fabiang\Sasl\Authentication\DigestMD5
{
    /**
     * Provides the (main) client response for DIGEST-MD5
     * requires a few extra parameters than the other
     * mechanisms, which are unavoidable.
     *
     * @param  string $challenge The digest challenge sent by the server
     * @return string            The digest response (NOT base64 encoded)
     */
    public function createResponse($challenge = null)
    {
        if(strpos($challenge, 'rspauth=') !== false) {
            return '';
        }

        return parent::createResponse($challenge);
    }

}