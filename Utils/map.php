<?php

namespace Kadet\Xmpp\Utils\map;

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
