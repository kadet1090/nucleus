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

namespace Kadet\Xmpp\Utils\filter;

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Utils\helper;

function xmlns($uri)
{
    return function (XmlElement $element) use ($uri) {
        return $element->namespace === $uri;
    };
}

function tag($name)
{
    return function (XmlElement $element) use ($name) {
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
