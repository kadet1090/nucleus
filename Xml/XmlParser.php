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

namespace Kadet\Xmpp\Xml;

use Kadet\Xmpp\Utils\BetterEmitter;
use Kadet\Xmpp\Utils\BetterEmitterInterface;

/**
 * Class XmlParser
 * @package Kadet\Xmpp\Xml
 *
 * @event element
 */
class XmlParser implements BetterEmitterInterface
{
    use BetterEmitter;

    /**
     * Factory used for XML element creation
     *
     * @var XmlElementFactory
     */
    public $factory;

    /**
     * XML element stack.
     *
     * @var XmlElement[]
     */
    private $stack = [];

    /**
     * XML parser resource
     *
     * @var resource
     */
    private $parser;

    /**
     * Document used as host for elements
     *
     * @var XmlDocument
     */
    private $document;

    /**
     * XmlParser constructor.
     *
     * @param XmlElementFactory $factory Factory used for XML element creation
     */
    public function __construct(XmlElementFactory $factory)
    {
        $this->factory = $factory;

        $this->reset();
    }

    /**
     * Resets state of xml parser.
     */
    public function reset()
    {
        $this->parser = xml_parser_create();

        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_element_handler($this->parser, function ($parser, $name, $attrs) {
            $this->handleElementStart($name, $attrs);
        }, function ($parser, $name) {
            $this->handleElementEnd();
        });

        xml_set_character_data_handler($this->parser, function ($parser, $data) {
            $this->handleTextData($data);
        });

        $this->document               = new XmlDocument();
        $this->document->formatOutput = true;

        $this->stack = [];
    }

    public function parse($data)
    {
        xml_parse($this->parser, $data);
    }

    private function _attributes($attrs)
    {
        $attributes = [];
        $namespaces = [];

        foreach ($attrs as $attr => $value) {
            if (strpos($attr, 'xmlns') === 0) {
                $namespaces[substr($attr, 6) ?: null] = $value;
            }

            $attributes[$attr] = $value;
        }

        return [$attributes, $namespaces];
    }

    private function _name($name)
    {
        $namespace = null;
        if (($pos = strpos($name, ':')) !== false) {
            $namespace = substr($name, 0, $pos);
            $name      = substr($name, $pos + 1);
        }

        return [$name, $namespace];
    }

    private function _lookup($prefix, $namespaces)
    {
        if ($prefix === 'xmlns') {
            return 'http://www.w3.org/2000/xmlns/';
        }

        return isset($namespaces[$prefix]) ? $namespaces[$prefix] : end($this->stack)->lookupNamespaceUri($prefix);
    }

    private function _element($name, $attrs)
    {
        list($attributes, $namespaces) = $this->_attributes($attrs);
        list($tag, $prefix)            = $this->_name($name);

        $uri   = $this->_lookup($prefix, $namespaces);
        $class = $this->factory->lookup($uri, $tag);

        /** @var XmlElement $element */
        $element = $this->document->importNode(new $class($name, null, $uri), true);
        foreach ($attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        return $element;
    }

    private function handleElementStart($name, $attrs)
    {
        $element = $this->_element($name, $attrs);

        if (count($this->stack) > 1) {
            end($this->stack)->appendChild($element);
        }
        $this->emit('parse.begin', [ $element ]);

        $this->stack[] = $element;
    }

    private function handleElementEnd()
    {
        if (empty($this->stack) === null) {
            return;
        }

        $element = array_pop($this->stack);
        if (count($this->stack) == 1) {
            $this->emit('element', [ $element ]);
        }

        $this->emit('parse.end', [ $element ]);
    }

    private function handleTextData($data)
    {
        if (trim($data)) {
            end($this->stack)->appendChild(new \DOMText($data));
        }
    }
}
