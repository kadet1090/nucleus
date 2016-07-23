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
use React\Stream\CompositeStream;
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
 * @event send.element
 * @event send.text
 *
 * @property-read $id
 * @property-read $from
 * @property-read $to
 * @property-read $version
 * @property-read $lang
 */
class XmlStream extends CompositeStream // implements BetterEmitterInterface // Some php cancer
{
    use BetterEmitter, Logging;

    /** XML namespace of stream */
    const NAMESPACE_URI = 'http://etherx.jabber.org/streams';

    /**
     * XmlParser reference
     *
     * @var XmlParser
     */
    protected $parser;

    /**
     * @var bool
     *
     * @see XmlStream::isOpened
     */
    private $isOpened = false;

    /**
     * Stream root element
     *
     * @var XmlElement
     */
    private $stream;

    /**
     * XmlStream constructor.
     *
     * Xml Stream acts like stream wrapper, that uses $transport stream to communicate with server.
     *
     * @param XmlParser             $parser    XmlParser instance used for converting XML to objects
     * @param DuplexStreamInterface $transport Stream used as the transport
     */
    public function __construct(XmlParser $parser, DuplexStreamInterface $transport)
    {
        parent::__construct($transport, $transport);

        $this->parser = $parser;

        $this->on('element', function (XmlStream $stream, Error $element) {
            $this->handleError($stream, $element);
        }, with\ofType(Error::class));

        $this->parser->on('parse.begin', function (XmlParser $parser, XmlElement $stream) {
            $this->stream = $stream;
            $this->emit('stream.open', [ $this, $stream ]);
        }, with\all(with\tag('stream'), with\xmlns(self::NAMESPACE_URI)));

        $this->parser->on('parse.end', function (XmlParser $parser, XmlElement $stream) {
            $this->emit('stream.close', [ $this, $stream ]);
            $this->stream = null;
        }, with\all(with\tag('stream'), with\xmlns(self::NAMESPACE_URI)));

        $this->on('data', [$this->parser, 'parse']);
        $this->parser->on('element', function ($parser, ...$arguments) {
            $this->emit('element', array_merge([ $this ], $arguments));
        });
        $this->on('close', function () { $this->isOpened = false; });
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
        $this->emit('send.'.($data instanceof XmlElement ? 'element' : 'text'), [ $this, $data ]);

        return parent::write($data);
    }

    /**
     * Starts new stream with specified attributes
     *
     * @param array $attributes Stream attributes
     */
    public function start(array $attributes = [])
    {
        $this->parser->reset();

        $this->write('<?xml version="1.0" encoding="utf-8"?>');

        $stream = XmlElement::create('stream:stream', null, 'http://etherx.jabber.org/streams');
        foreach ($attributes as $key => $value) {
            $stream->setAttribute($key, $value);
        }

        $this->write(preg_replace('~/>$~', '>', $stream));
        $this->isOpened = true;
    }

    /**
     * Gently closes stream
     */
    public function close()
    {
        if($this->isOpened()) {
            $this->write('</stream:stream>');
            $this->isOpened = false;
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
        return $this->isOpened;
    }

    public function __get($name)
    {
        return $this->stream->getAttribute($name === 'lang' ? 'xml:lang' : $name);
    }

    public function __set($name, $value)
    {
        throw new ReadOnlyException('Stream attributes are read-only.');
    }

    public function __isset($name)
    {
        return $this->stream->hasAttribute($name);
    }

    private function handleError(XmlStream $stream, Error $element)
    {
        if($this->emit('stream.error', [ $this, $element ])) {
            throw new StreamErrorException($element);
        }

        return false;
    }
}
