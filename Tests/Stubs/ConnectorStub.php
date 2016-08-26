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

namespace Kadet\Xmpp\Tests\Stubs;


use Kadet\Xmpp\Network\Connector;
use Kadet\Xmpp\Utils\BetterEmitter;
use Kadet\Xmpp\Utils\Logging;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Stream\CompositeStream;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;

class ConnectorStub implements Connector
{
    use Logging, BetterEmitter;

    private $_loop;
    private $_stream;

    /**
     * ConnectorStub constructor.
     * @param DuplexStreamInterface $stream Stream returned in connect
     */
    public function __construct(DuplexStreamInterface $stream = null)
    {
        $this->_loop = Factory::create();
        $this->_stream = $stream ?: new CompositeStream(
            new ThroughStream(),
            new ThroughStream()
        );
    }

    public function connect(array $options = []) : DuplexStreamInterface
    {
        $this->emit('connect', [ $this->_stream ]);
        return $this->_stream;
    }

    public function getLoop() : LoopInterface
    {
        return $this->_loop;
    }
}
