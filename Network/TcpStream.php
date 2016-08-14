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

namespace Kadet\Xmpp\Network;

use React\Stream\Stream;

class TcpStream extends Stream implements SecureStream
{
    private $_secured = false;

    public function encrypt(int $type = STREAM_CRYPTO_METHOD_ANY_CLIENT) : bool
    {
        if ($this->_secured) {
            return true;
        }

        $result = true;
        $result &= stream_set_blocking($this->stream, 1);
        $result &= stream_socket_enable_crypto($this->stream, true, $type);
        $result &= stream_set_blocking($this->stream, 0);

        return $this->_secured = $result;
    }

    public function decrypt() : bool
    {
        if (!$this->_secured) {
            return true;
        }

        $result = true;
        $result &= stream_set_blocking($this->stream, 1);
        $result &= stream_socket_enable_crypto($this->stream, false);
        $result &= stream_set_blocking($this->stream, 0);

        return !($this->_secured = !$result);
    }
}
