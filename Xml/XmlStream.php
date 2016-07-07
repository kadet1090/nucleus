<?php
/**
 * XMPP Library
 *
 * Copyright (C) 2016, Some right reserved.
 */

namespace Kadet\Xmpp\Xml;

use Evenement\EventEmitterTrait;
use React\Stream\CompositeStream;
use React\Stream\DuplexStreamInterface;


/**
 * Class XmlStream
 *
 * @package Kadet\Xmpp\Xml
 *
 * @event element
 * @event stream-error
 */
class XmlStream extends CompositeStream
{
    use EventEmitterTrait;

    const NAMESPACE_URI = 'http://etherx.jabber.org/streams';

    private $parser;

    /** @var \DOMDocument */
    private $stream;

    /** @var \DOMElement[] */
    private $stack = [];

    private $factory;
    private $isOpened = false;

    public function __construct(XmlElementFactory $factory, DuplexStreamInterface $stream) {
        $this->setupParser();

        $this->stream = new XmlDocument();
        $this->stream->formatOutput = true;

        $this->factory = $factory;

        parent::__construct($stream, $stream);

        $this->on('data', [$this, 'parse']);
        $this->on('element', function(XmlElement $element) { $this->handleError($element); });
        $this->write('<?xml version="1.0" encoding="utf-8"?>');
    }

    public function parse($data) {
        xml_parse($this->parser, $data);
    }

    private function _attributes($attrs) {
        $attributes = [];
        $namespaces = [];

        foreach($attrs as $attr => $value) {
            if(strpos($attr, 'xmlns') === 0) {
                $namespaces[substr($attr, 6)] = $value;
            }

            $attributes[$attr] = $value;
        }

        return [$attributes, $namespaces];
    }

    private function _name($name)
    {
        $namespace = null;
        if(($pos = strpos($name, ':')) !== false) {
            $namespace = substr($name, 0, $pos);
            $name = substr($name, $pos + 1);
        }

        return [$name, $namespace];
    }

    private function _lookup($prefix, $namespaces)
    {
        if($prefix === 'xmlns') {
            return 'http://www.w3.org/2000/xmlns/';
        }

        return isset($namespaces[$prefix]) ? $namespaces[$prefix] : end($this->stack)->lookupNamespaceUri($prefix);
    }

    private function _element($name, $attrs)
    {
        list($attributes, $namespaces) = $this->_attributes($attrs);
        list($tag, $prefix) = $this->_name($name);

        $uri = $this->_lookup($prefix, $namespaces);
        $class = $this->factory->lookup($uri, $tag);

        /** @var XmlElement $element */
        $element = $this->stream->importNode(new $class($name, null, $uri), true);

        foreach ($attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        return $element;
    }

    private function handleElementStart($name, $attrs) {
        $element = $this->_element($name, $attrs);

        if($element->localName === 'stream' && $element->namespaceURI === static::NAMESPACE_URI) {
            $this->stream->appendChild($element);
        } elseif(count($this->stack) > 1) {
            end($this->stack)->appendChild($element);
        }

        $this->stack[] = $element;
    }

    private function handleElementEnd($name) {
        if(empty($this->stack) === null) {
            return;
        }

        $element = array_pop($this->stack);
        if(count($this->stack) == 1) {
            $this->emit('element', [$element]);
        }
    }

    private function handleTextData($data) {
        if(trim($data)) {
            end($this->stack)->appendChild(new \DOMText($data));
        }
    }

    public function start(array $attributes = [])
    {
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

    public function isOpened() {
        return $this->isOpened;
    }

    protected function setupParser()
    {
        $this->parser = xml_parser_create();

        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_element_handler($this->parser, function ($parser, $name, $attrs) {
            $this->handleElementStart($name, $attrs);
        }, function ($parser, $name) {
            $this->handleElementEnd($name);
        });

        xml_set_character_data_handler($this->parser, function ($parser, $data) {
            $this->handleTextData($data);
        });
    }

    private function handleError(XmlElement $element)
    {
        if($element->localName === 'error' && $element->namespaceURI === static::NAMESPACE_URI) {
            $this->emit('stream-error', [ $element ]);
        }
    }
}
