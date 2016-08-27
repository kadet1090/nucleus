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

namespace Kadet\Xmpp\Utils\helper;

use Kadet\Xmpp\Utils\Dumper;

/**
 * Returns exception friendly type of value.
 *
 * @param $value
 * @return string
 */
function typeof($value) : string
{
    if (is_object($value)) {
        return "object of type ".get_class($value);
    } elseif (is_resource($value)) {
        return get_resource_type($value).' resource';
    }

    return gettype($value);
}

function partial(callable $callable, $argument, int $position = 0) : callable
{
    return function (...$arguments) use ($callable, $argument, $position) {
        $arguments = array_merge(
            array_slice($arguments, 0, $position),
            [ $argument ],
            array_slice($arguments, $position)
        );

        return $callable(...$arguments);
    };
}

function dd($value)
{
    echo Dumper::get()->dump($value).PHP_EOL.PHP_EOL;
}

function format($string, array $arguments = [])
{
    return str_replace(array_map(function ($e) { return "{{$e}}"; }, array_keys($arguments)), $arguments, $string);
}

function rearrange(array $array, array $keys) : array
{
    uksort($array, function($a, $b) use($keys) {
        return ($keys[$b] ?? 0) <=> ($keys[$a] ?? 0);
    });

    return $array;
}
