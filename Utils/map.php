<?php

namespace Kadet\Xmpp\Utils\map;

use Kadet\Xmpp\Xml\XmlElement;

function prepend(string $prefix) {
    return function($content) use ($prefix) {
        return $prefix . $content;
    };
}

function append(string $suffix) {
    return function($content) use ($suffix) {
        return $content . $suffix;
    };
}

function element(string $name, string $uri = null, $class = XmlElement::class) {
    return function($item) use ($class) {
        return new $class('group', null, ['content' => $item]);
    };
}