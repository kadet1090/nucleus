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

namespace Kadet\Xmpp\Tests\Stubs;


use Kadet\Xmpp\Network\Connector;
use Kadet\Xmpp\Utils\BetterEmitter;
use Kadet\Xmpp\Utils\Logging;
use React\EventLoop\Factory;
use React\Stream\CompositeStream;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;

class ConnectorStub implements Connector
{
    use Logging, BetterEmitter;

    private $_loop;

    /**
     * ConnectorStub constructor.
     */
    public function __construct()
    {
        $this->_loop = Factory::create();
    }

    public function connect(array $options = []) : DuplexStreamInterface
    {
        $stream = new CompositeStream(
            new ThroughStream(),
            new ThroughStream()
        );

        $this->emit('connect', [$stream]);
        return $stream;
    }

    public function getLoop()
    {
        return $this->_loop;
    }
}
