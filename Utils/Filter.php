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

/**
 * Predicate used to check if argument is equal (loosely) to specified value.
 *
 * ```php
 * $predicate = equals(10);
 *
 * $predicate(10); // true, as 10 == 10
 * $predicate("10abc"); // true, as 10 == "10abc"
 * $predicate("abc"); // false, as 10 != "abc"
 * ```
 *
 * @param $value
 * @return \Closure
 */
function equals($value) : \Closure
{
    return function ($argument) use ($value) {
        return $argument == $value;
    };
}

/**
 * Predicate used to check if argument is same as specified value. (strict comparision)
 *
 * ```php
 * $predicate = equals(10);
 *
 * $predicate(10); // true, as 10 === 10
 * $predicate("10abc"); // false, as 10 !== "10abc"
 * $predicate("abc"); // false, as 10 !== "abc"
 * ```
 *
 * @param $value
 * @return \Closure
 */
function same($value) : \Closure
{
    return function ($argument) use ($value) {
        return $argument === $value;
    };
}

/**
 * Predicate used to check if argument is an instance of specified class.
 *
 * ```php
 * $predicate = instance(Foo::class);
 *
 * $predicate(new Foo); // true
 * $predicate(new \DateTime); // false
 * $predicate(new class extends Foo {}); // true, as anonymous class extends Foo
 * ```
 *
 * @param string $class Desired class name.
 *
 * @return \Closure
 */
function instance($class) : \Closure
{
    return function ($object) use ($class) {
        return $object instanceof $class;
    };
}

/**
 * Returns constant function, that always returns specified value.
 *
 * ```php
 * $true   = constant(true);
 * $string = constant("foo");
 *
 * $true();   // true
 * $string(); // string(3) "foo"
 * ```
 *
 * @param mixed $return
 * @return \Closure
 */
function constant($return) : \Closure
{
    return function() use ($return) {
        return $return;
    };
}

/**
 * Predicate used to check if arguments matches all specified predicates. It mimics and operator behaviour.
 *
 * ```php
 * $instance = instance(Foo::class);
 * $true     = constant(true);
 *
 * $foo = new Foo;
 *
 * $predicate = all($instance, $true);
 * $predicate($foo); // true, it's virtually same as $instance($foo) && $true($foo)
 * ```
 *
 * @param \callable[] ...$functions
 * @return \Closure
 */
function all(callable ...$functions) : \Closure
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

/**
 * Predicate used to check if arguments matches any of specified predicates. It mimics or operator behaviour.
 *
 * ```php
 * $instance = instance(Foo::class);
 * $false    = constant(false);
 *
 * $foo = new Foo;
 *
 * $predicate = any($instance, $false);
 * $predicate($foo); // true, it's virtually same as $instance($foo) || $false($foo)
 * ```
 *
 * @param \callable[] ...$functions
 * @return \Closure
 */
function any(callable ...$functions) : \Closure
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

/**
 * Tries to figure best predicate for specified argument.
 *
 * For predicates returns that predicate, for class names returns `instance($predicate)`, and for other values returns
 * `equals($predicate)` or `same($predicate)`, depending on $strict argument.
 *
 * @param      $predicate
 * @param bool $strict    Set to true if value has to be matched strictly.
 * @return \Closure
 */
function predicate($predicate, bool $strict = false) : \Closure
{
    if ($predicate instanceof \Closure) {
        return $predicate;
    } elseif (class_exists($predicate)) {
        return instance($predicate);
    } else {
        return $strict ? same($predicate) : equals($predicate);
    }
}

/**
 * Negates predicate specified in argument.
 *
 * ```php
 * not(constant(false))() // true, as !false === true
 * ```
 *
 * @param callable $predicate
 * @return \Closure
 */
function not(callable $predicate) : \Closure
{
    return function (...$arguments) use ($predicate) {
        return !$predicate(...$arguments);
    };
}

/**
 * Helper function used to bind argument to predicate. It can be used when called arguments order do not match arguments
 * that expected by predicate.
 *
 * For example, `instance` predicate checks if first argument is instance of specified class, but argument we need to
 * check is the second one, so we need to wrap it with `argument` helper:
 *
 * ```php
 * $predicate = argument(instance(Foo::class), 2);
 * var_dump($predicate("smth", new Foo)); // true as second argument matches instance predicate
 * var_dump($predicate(new Foo, "smth")); // true as second argument does not match instance predicate
 * ```
 *
 * @param callable $predicate Predicate to match on specified offset
 * @param int      $offset    Argument offset
 * @param bool|int $length    [optional]
 *                            `true`:  will return ONLY argument at $offset
 *                            `false`: will return all arguments from $offset
 *                            int:     will return $length arguments from $offset
 *
 * @return \Closure
 */
function argument(callable $predicate, int $offset, $length = true) : \Closure
{
    if($length === true) {
        $length = 1;
    } elseif($length === false) {
        $length = null;
    }

    return function (...$arguments) use ($predicate, $offset, $length) {
        $predicate(...array_slice($arguments, $offset, $length, false));
    };
}

/**
 * Shorthand for calling
 *
 * ```php
 * all(element\name($name), element\xmlns($uri))
 * ```
 *
 * @see \Kadet\Xmpp\Utils\filter\element\name($name)
 * @see \Kadet\Xmpp\Utils\filter\element\xmlns($uri)
 *
 * @param string $name Element name
 * @param string $uri  Element namespace
 * @return \Closure
 */
function element(string $name, string $uri)
{
    return all(element\name($name), element\xmlns($uri));
}

