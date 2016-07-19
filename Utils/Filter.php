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

namespace Kadet\Xmpp\Utils;


use Kadet\Xmpp\Xml\XmlElement;

abstract class Filter
{
    public static function xmlns($uri) {
        return function (XmlElement $element) use($uri) {
            return $element->namespaceURI === $uri;
        };
    }

    public static function tag($name) {
        return function (XmlElement $element) use($name) {
            return $element->localName === $name;
        };
    }

    public static function typeof($class) {
        return function ($element) use($class) {
            return $element instanceof $class;
        };
    }

    public static function all(callable ...$functions) {
        return function (...$args) use($functions) {
            foreach ($functions as $function) {
                if(!$function(...$args)) {
                    return false;
                }
            }

            return true;
        };
    }

    public static function oneOf(callable ...$functions) {
        return function (...$args) use($functions) {
            foreach ($functions as $function) {
                if($function(...$args)) {
                    return true;
                }
            }

            return false;
        };
    }
}
