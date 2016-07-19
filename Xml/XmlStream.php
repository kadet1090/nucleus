<?php
/**
 * XMPP Library
 *
 * Copyright (C) 2016, Some right reserved.
 */

namespace Kadet\Xmpp\Xml;

use Evenement\EventEmitterInterface;
use Kadet\Xmpp\Utils\BetterEmitter;
use React\Stream\CompositeStream;
use React\Stream\DuplexStreamInterface;
use React\Stream\Util;
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
class XmlStream extends CompositeStream implements EventEmitterInterface
{
    use BetterEmitter;

    const NAMESPACE_URI = 'http://etherx.jabber.org/streams';

    /**
     * XmlParser reference
     *
     * @var XmlParser
     */
    protected $parser;
    private $isOpened = false;

    /** @var XmlElement  */
    private $stream;

    public function __construct(XmlParser $parser, DuplexStreamInterface $stream)
    {
        parent::__construct($stream, $stream);

        $this->parser = $parser;

        $this->on('element', function (XmlElement $element) {
            $this->handleError($element);
        }, with\all(with\tag('error'), with\xmlns(self::NAMESPACE_URI)));

        $this->parser->on('parse.begin', function (XmlElement $stream) {
            $this->stream = $stream;
            $this->emit('stream.open', [ $stream ]);
        }, with\all(with\tag('stream'), with\xmlns(self::NAMESPACE_URI)));

        $this->on('data', [$this->parser, 'parse']);
        $this->on('close', function () { $this->isOpened = false; });

        Util::forwardEvents($this->parser, $this, ['element']);
    }

    private function handleError(XmlElement $element)
    {
        $this->emit('stream.error', [ $element ]);
    }

    public function write($data)
    {
        $this->emit('send.'.($data instanceof XmlElement ? 'element' : 'text'), [ $data ]);

        return parent::write($data);
    }

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

    public function close()
    {
        $this->write('</stream:stream>');
        $this->isOpened = false;

        parent::close();
    }

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
        throw new \LogicException('Stream attributes are read-only.'); // todo: proper exception
    }

    public function __isset($name)
    {
        return $this->stream->hasAttribute($name);
    }
}
