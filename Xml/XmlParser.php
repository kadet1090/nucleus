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
    private $_stack = [];

    /**
     * XML parser resource
     *
     * @var resource
     */
    private $_parser;

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
        $this->_parser = xml_parser_create();

        xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_element_handler($this->_parser, function ($parser, $name, $attrs) {
            $this->handleElementStart($name, $attrs);
        }, function () {
            $this->handleElementEnd();
        });

        xml_set_character_data_handler($this->_parser, function ($parser, $data) {
            $this->handleTextData($data);
        });

        $this->_stack = [];
    }

    public function parse($data)
    {
        xml_parse($this->_parser, $data);
    }

    private function _attributes($attrs)
    {
        $attributes = [];
        $namespaces = [];

        foreach ($attrs as $attr => $value) {
            if (strpos($attr, 'xmlns') === 0) {
                $namespaces[substr($attr, 6) ?: null] = $value;
            } else {
                $attributes[$attr] = $value;
            }
        }

        return [$attributes, $namespaces];
    }

    private function _lookup($prefix, $namespaces)
    {
        if ($prefix === 'xmlns') {
            return 'http://www.w3.org/2000/xmlns/';
        }

        return isset($namespaces[$prefix]) ? $namespaces[$prefix] : end($this->_stack)->lookupUri($prefix);
    }

    private function _element($name, $attrs)
    {
        list($attributes, $namespaces) = $this->_attributes($attrs);
        list($tag, $prefix)            = XmlElement::resolve($name);

        $uri   = $this->_lookup($prefix, $namespaces);

        /** @var XmlElement $element */
        $element = $this->factory->create($uri, $tag, [ $name, $uri ]);

        foreach ($namespaces as $prefix => $uri) {
            $element->setNamespace($uri, $prefix);
        }
        foreach ($attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        return $element;
    }

    private function handleElementStart($name, $attrs)
    {
        $element = $this->_element($name, $attrs);

        if (count($this->_stack) > 1) {
            end($this->_stack)->append($element);
        }
        $this->emit('parse.begin', [ $element ]);

        $this->_stack[] = $element;
    }

    private function handleElementEnd()
    {
        if (empty($this->_stack) === null) {
            return;
        }

        $element = array_pop($this->_stack);
        if (count($this->_stack) == 1) {
            $this->emit('element', [ $element ]);
        }

        $this->emit('parse.end', [ $element ]);
    }

    private function handleTextData($data)
    {
        if (trim($data)) {
            end($this->_stack)->append($data);
        }
    }
}
