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

namespace Kadet\Xmpp\Connection;

use Evenement\EventEmitterInterface;

interface Connection extends EventEmitterInterface
{
    public function connect() : bool;
    public function disconnect();

    public function isConnected() : bool;

    public function send(string $data) : bool;
    public function receive() : bool;

    public function startTLS() : bool;
}

