<?php
/**
 * XMPP Library
 *
 * Copyright (C) 2016, Some right reserved.
 */

namespace Kadet\Xmpp\Xml;

use Kadet\Xmpp\Exception\Protocol\StreamErrorException;
use Kadet\Xmpp\Exception\ReadOnlyException;
use Kadet\Xmpp\Stream\Error;
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
 * @event element
 * @event stream.error
 * @event stream.open
 * @event stream.close
 * @event send.element
 * @event send.text
 *
 * @property-read $id
 * @property-read $from
 * @property-read $to
 * @property-read $version
 * @property-read $lang
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
     * Stream root element
     *
     * @var XmlElement
     */
    private $_stream;

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

        $this->_parser = $parser;

        $this->on('element', function (Error $element) {
            $this->handleError($element);
        }, with\ofType(Error::class));

        $this->_parser->on('parse.begin', function (XmlElement $stream) {
            $this->_stream = $stream;
            $this->emit('stream.open', [ $stream ]);
        }, with\all(with\tag('stream'), with\xmlns(self::NAMESPACE_URI)));

        $this->_parser->on('parse.end', function (XmlElement $stream) {
            $this->emit('stream.close', [ $stream ]);
            $this->_stream = null;
        }, with\all(with\tag('stream'), with\xmlns(self::NAMESPACE_URI)));

        $this->on('data', [$this->_parser, 'parse']);
        $this->_parser->on('element', function (...$arguments) {
            $this->emit('element', $arguments);
        });
        $this->on('close', function () { $this->_isOpened = false; });
    }

    /**
     * Writes data to stream
     *
     * @param  string $data Data to write
     *
     * @return bool
     */
    public function write($data)
    {
        $this->emit('send.'.($data instanceof XmlElement ? 'element' : 'text'), [ $data ]);

        return parent::write($data);
    }

    /**
     * Starts new stream with specified attributes
     *
     * @param array $attributes Stream attributes
     */
    public function start(array $attributes = [])
    {
        $this->_parser->reset();

        $this->write('<?xml version="1.0" encoding="utf-8"?>');

        $stream = XmlElement::create('stream:stream', null, 'http://etherx.jabber.org/streams');
        foreach ($attributes as $key => $value) {
            $stream->setAttribute($key, $value);
        }

        $this->write(preg_replace('~/>$~', '>', $stream));
        $this->_isOpened = true;
    }

    /**
     * Gently closes stream
     */
    public function close()
    {
        if($this->isOpened()) {
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
        return $this->_stream->getAttribute($name === 'lang' ? 'xml:lang' : $name);
    }

    public function __set($name, $value)
    {
        throw new ReadOnlyException('Stream attributes are read-only.');
    }

    public function __isset($name)
    {
        return $this->_stream->hasAttribute($name);
    }

    private function handleError(Error $element)
    {
        if($this->emit('stream.error', [ $element ])) {
            throw new StreamErrorException($element);
        }

        return false;
    }
}
