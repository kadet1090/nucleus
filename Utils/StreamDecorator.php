<?php declare(strict_types=1);
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

    private $_listeners;

    /**
     * @var DuplexStreamInterface
     */
    protected $decorated = null;

    /**
     * StreamDecorator constructor.
     * @param DuplexStreamInterface $decorated
     */
    public function __construct(DuplexStreamInterface $decorated)
    {
        $this->exchangeStream($decorated);
    }

    public function isReadable()
    {
        return $this->decorated->isReadable();
    }

    public function pause()
    {
        $this->decorated->pause();
    }

    public function resume()
    {
        $this->decorated->resume();
    }

    public function pipe(WritableStreamInterface $destination, array $options = array())
    {
        $this->decorated->pipe($destination, $options);
    }

    public function close()
    {
        $this->decorated->close();
    }

    public function isWritable()
    {
        return $this->decorated->isWritable();
    }

    public function write($data)
    {
        return $this->decorated->write($data);
    }

    public function end($data = null)
    {
        return $this->decorated->end($data);
    }

    public function exchangeStream(DuplexStreamInterface $stream)
    {
        static $events = ['data', 'end', 'drain', 'error', 'close', 'pipe'];

        if($this->decorated !== null) {
            $this->unsubscribe($this->decorated, $events);
        }

        $this->subscribe($stream, $events);
        $this->decorated = $stream;
    }

    private function unsubscribe(DuplexStreamInterface $stream, array $events)
    {
        foreach ($events as $event) {
            if(!isset($this->_listeners[$event])) {
                continue;
            }

            $stream->removeListener($event, $this->_listeners[$event]);
        }
    }

    private function subscribe(DuplexStreamInterface $stream, array $events)
    {
        foreach ($events as $event) {
            if(!isset($this->_listeners[$event])) {
                $this->_listeners[$event] = function (...$arguments) use ($event) {
                    $this->emit($event, $arguments);
                };
            }

            $stream->on($event, $this->_listeners[$event]);
        }
    }


}
