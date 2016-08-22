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

class XmlElementFactory
{
    /**
     * @var string[string]
     */
    private $_lookup = ['<predicate>' => ''];
    
    public function lookup($namespace, $tag, $additional = [])
    {
        $lookup = array_merge($this->_lookup, $this->_lookupize($additional));

        if (isset($lookup["$tag@$namespace"])) {
            return $lookup["$tag@$namespace"];
        } elseif (isset($lookup[$namespace])) {
            return $lookup[$namespace];
        } else {
            return XmlElement::class;
        }
    }

    public function register($class, $namespace, $tag = null)
    {
        $this->_lookup = array_merge($this->_lookup, $this->_lookupize([[$class, 'uri' => $namespace, 'name' => $tag]]));
    }

    public function load(array $dictionary)
    {
        foreach ($dictionary as $element) {
            $this->register($element[0], $element['uri'] ?? null, $element['name'] ?? null);
        }
    }

    public function create($namespace, $tag, $arguments = [], $additional = [])
    {
        $class = $this->lookup($namespace, $tag, $additional);
        /** @noinspection PhpUndefinedMethodInspection */
        return $class::plain(...$arguments);
    }

    private function _lookupize(array $dictionary)
    {
        $result = [];
        foreach ($dictionary as $element) {
            $result[$this->_name($element['name'] ?? null, $element['uri'] ?? null)] = $element[0];
        }

        return $result;
    }

    private function _name(string $name = null, string $uri = null)
    {
        return $name ? "$name@$uri" : $uri;
    }
}
