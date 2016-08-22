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

namespace Kadet\Xmpp\Utils\filter;

require __DIR__ . '/Filter/element.php';
require __DIR__ . '/Filter/stanza.php';

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Utils\helper;

function instance($class)
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
        return instance($predicate);
    } else {
        throw new InvalidArgumentException('$condition must be either class-name or callable, ' . helper\typeof($predicate) . ' given.');
    }
}

function not(callable $predicate)
{
    return function (...$arguments) use ($predicate) {
        return !$predicate(...$arguments);
    };
}

function arguments(callable $predicate, int $offset, int $length = null)
{
    return function (...$arguments) use ($predicate, $offset, $length) {
        $predicate(...array_slice($arguments, $offset, $length, false));
    };
}

function element(string $name, string $uri)
{
    return all(element\name($name), element\xmlns($uri));
}

