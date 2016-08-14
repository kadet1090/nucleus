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

declare (strict_types = 1);
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

use React\Stream\DuplexStreamInterface;
use React\Stream\WritableStreamInterface;

abstract class StreamDecorator implements DuplexStreamInterface
{
    use BetterEmitter;

    private $_redirectors;

    /**
     * @var DuplexStreamInterface
     */
    protected $_decorated = null;

    /**
     * StreamDecorator constructor.
     * @param DuplexStreamInterface $decorated
     */
    public function __construct(DuplexStreamInterface $decorated = null)
    {
        if ($decorated !== null) {
            $this->exchangeStream($decorated);
        }
    }

    public function isReadable()
    {
        return $this->_decorated->isReadable();
    }

    public function pause()
    {
        $this->_decorated->pause();
    }

    public function resume()
    {
        $this->_decorated->resume();
    }

    public function pipe(WritableStreamInterface $destination, array $options = array())
    {
        $this->_decorated->pipe($destination, $options);
    }

    public function close()
    {
        $this->_decorated->close();
    }

    public function isWritable()
    {
        return $this->_decorated->isWritable();
    }

    public function write($data)
    {
        return $this->_decorated->write($data);
    }

    public function end($data = null)
    {
        return $this->_decorated->end($data);
    }

    public function exchangeStream(DuplexStreamInterface $stream)
    {
        static $events = ['data', 'end', 'drain', 'error', 'close', 'pipe'];

        if ($this->_decorated !== null) {
            $this->unsubscribe($this->_decorated, $events);
        }

        $this->subscribe($stream, $events);
        $this->_decorated = $stream;
    }

    private function unsubscribe(DuplexStreamInterface $stream, array $events)
    {
        foreach ($events as $event) {
            if (!isset($this->_redirectors[$event])) {
                continue;
            }

            $stream->removeListener($event, $this->_redirectors[$event]);
        }
    }

    private function subscribe(DuplexStreamInterface $stream, array $events)
    {
        foreach ($events as $event) {
            if (!isset($this->_redirectors[$event])) {
                $this->_redirectors[$event] = function (...$arguments) use ($event) {
                    $this->emit($event, $arguments);
                };
            }

            $stream->on($event, $this->_redirectors[$event]);
        }
    }

    public function getDecorated() : DuplexStreamInterface
    {
        return $this->_decorated;
    }
}
