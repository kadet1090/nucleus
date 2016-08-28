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

namespace Kadet\Xmpp\Xml;

use Kadet\Xmpp\Exception\Protocol\StreamErrorException;
use Kadet\Xmpp\Exception\ReadOnlyException;
use Kadet\Xmpp\Stream\Error;
use Kadet\Xmpp\Stream\Stream;
use Kadet\Xmpp\Utils\BetterEmitter;
use Kadet\Xmpp\Utils\Logging;
use Kadet\Xmpp\Utils\StreamDecorator;
use React\Stream\DuplexStreamInterface;
use Kadet\Xmpp\Utils\filter as with;

/**
 * Class XmlStream
 *
 * @package Kadet\Xmpp\Xml
 *
 * @event element(XmlElement $element)
 * @event stream.error(
 * @event stream.open
 * @event stream.close
 * @event send.element
 * @event send.text
 *
 * @property-read string $id
 * @property-read string $from
 * @property-read string $to
 * @property-read string $version
 * @property-read string $lang
 */
class XmlStream extends StreamDecorator // implements BetterEmitterInterface // Some php cancer
{
    use BetterEmitter, Logging;

    /** XML namespace of stream */
    const NAMESPACE_URI = 'http://etherx.jabber.org/streams';

    /**
     * XmlParser reference
     *
     * @var XmlParser
     */
    protected $_parser;

    /**
     * @var bool
     *
     * @see XmlStream::isOpened
     */
    private $_isOpened = false;

    /**
     * Inbound Stream root element
     *
     * @var XmlElement
     */
    private $_inbound;

    /**
     * Outbound Stream root element
     *
     * @var XmlElement
     */
    private $_outbound;

    private $_attributes = [];

    /**
     * XmlStream constructor.
     *
     * Xml Stream acts like stream wrapper, that uses $transport stream to communicate with server.
     *
     * @param XmlParser             $parser    XmlParser instance used for converting XML to objects
     * @param DuplexStreamInterface $transport Stream used as the transport
     */
    public function __construct(XmlParser $parser, DuplexStreamInterface $transport = null)
    {
        parent::__construct($transport);
        $this->setParser($parser);

        $this->on('close', function () { $this->_isOpened = false; });
        $this->on('element', function (Error $element) {
            $this->handleError($element);
        }, with\instance(Error::class));
    }

    public function setParser(XmlParser $parser)
    {
        if($this->_parser) {
            $this->removeListener('data', [ $this->_parser, 'parse' ]);
        }

        $this->_parser = $parser;

        $this->_parser->on('parse.begin', function (XmlElement $stream) {
            $this->_inbound = $stream;
            $this->emit('stream.open', [ $stream ]);
        }, with\argument(1, with\equals(0)));

        $this->_parser->on('parse.end', function (XmlElement $stream) {
            $this->emit('stream.close', [ $stream ]);
            $this->_inbound = null;
        }, with\argument(1, with\equals(0)));

        $this->_parser->on('element', function (...$arguments) {
            $this->emit('element', $arguments);
        });

        $this->on('data', [ $this->_parser, 'parse' ]);
    }

    /**
     * Writes data to stream
     *
     * @param  string $data Data to write
     * @return bool
     */
    public function write($data)
    {
        if($data instanceof XmlElement) {
            $this->_outbound->append($data);
        }

        $this->emit('send.'.($data instanceof XmlElement ? 'element' : 'text'), [ $data ]);

        return parent::write($data);
    }

    /**
     * Starts new stream with specified attributes
     *
     * @param array  $attributes Stream attributes
     */
    public function start(array $attributes = [])
    {
        $this->_parser->reset();
        $this->_attributes = $attributes;

        $this->write('<?xml version="1.0" encoding="utf-8"?>');
        $this->_outbound = new Stream($attributes);

        $this->write(preg_replace('~\s+/>$~', '>', $this->_outbound));
        $this->_isOpened = true;
    }

    public function restart()
    {
        $this->getLogger()->debug('Restarting stream', $this->_attributes);
        $this->start($this->_attributes);
    }

    /**
     * Gently closes stream
     */
    public function close()
    {
        if ($this->isOpened()) {
            $this->write('</stream:stream>');
            $this->_isOpened = false;
        }

        parent::close();
    }

    /**
     * Checks if stream is opened
     *
     * @return bool
     */
    public function isOpened()
    {
        return $this->_isOpened;
    }

    public function __get($name)
    {
        return $this->_inbound->$name;
    }

    public function __set($name, $value)
    {
        throw new ReadOnlyException('Stream attributes are read-only.');
    }

    public function __isset($name)
    {
        return $this->_inbound->hasAttribute($name);
    }

    private function handleError(Error $element)
    {
        if ($this->emit('stream.error', [ $element ])) {
            throw new StreamErrorException($element);
        }

        return false;
    }
}
