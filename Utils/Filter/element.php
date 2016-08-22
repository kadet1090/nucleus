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

namespace Kadet\Xmpp\Utils\filter\element;

use Kadet\Xmpp\Xml\XmlElement;


function xmlns($uri)
{
    return function ($element) use ($uri) {
        if (!$element instanceof XmlElement) {
            return false;
        }

        return $element->namespace === $uri;
    };
}

function name($name)
{
    return function ($element) use ($name) {
        if (!$element instanceof XmlElement) {
            return false;
        }

        return $element->localName === $name;
    };
}

function attribute($name, $value)
{
    return function ($element) use ($name, $value) {
        if (!$element instanceof XmlElement) {
            return false;
        }

        return is_callable($value) ? $value($element->getAttribute($name)) : $element->getAttribute($name) === $value;
    };
}
