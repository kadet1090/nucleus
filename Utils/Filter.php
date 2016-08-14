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

namespace Kadet\Xmpp\Utils\filter {
    use Kadet\Xmpp\Exception\InvalidArgumentException;
    use Kadet\Xmpp\Xml\XmlElement;
    use Kadet\Xmpp\Utils\helper;

    function element(string $name, string $uri)
    {
        return all(tag($name), xmlns($uri));
    }

    function xmlns($uri)
    {
        return function ($element) use ($uri) {
            if(!$element instanceof XmlElement) {
                return false;
            }

            return $element->namespace === $uri;
        };
    }

    function tag($name)
    {
        return function ($element) use ($name) {
            if(!$element instanceof XmlElement) {
                return false;
            }

            return $element->localName === $name;
        };
    }

    function ofType($class)
    {
        return function ($object) use ($class) {
            return $object instanceof $class;
        };
    }

    function all(callable ...$functions)
    {
        return function (...$args) use ($functions) {
            foreach ($functions as $function) {
                if (!$function(...$args)) {
                    return false;
                }
            }

            return true;
        };
    }

    function any(callable ...$functions)
    {
        return function (...$args) use ($functions) {
            foreach ($functions as $function) {
                if ($function(...$args)) {
                    return true;
                }
            }

            return false;
        };
    }

    function predicate($predicate) : callable
    {
        if (is_callable($predicate)) {
            return $predicate;
        } elseif (class_exists($predicate)) {
            return ofType($predicate);
        } else {
            throw new InvalidArgumentException('$condition must be either class-name or callable, ' . helper\typeof($predicate) . ' given.');
        }
    }

    function argument($name, $value) {
        return function($element) use ($name, $value) {
            if(!$element instanceof XmlElement) {
                return false;
            }

            return is_callable($value) ? $value($element->getAttribute($name)) : $element->getAttribute($name) === $value;
        };
    }
}

namespace Kadet\Xmpp\Utils\filter\stanza {
    use Kadet\Xmpp\Utils\filter;

    function id($id) {
        return filter\argument('id', $id);
    }

    function type($type) {
        return filter\argument('type', $type);
    }
}
