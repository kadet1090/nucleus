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

namespace Kadet\Xmpp\Tests;

use function Kadet\Xmpp\Utils\filter\equals;
use function Kadet\Xmpp\Utils\filter\pass;

interface Kek {}
class Foo {}
class Bar {}
class Lel implements Kek {}

class PredicateTest extends \PHPUnit_Framework_TestCase
{
    public function testEqualsPredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\equals(10);

        $this->assertTrue($predicate(10));
        $this->assertTrue($predicate("10"));
        $this->assertTrue($predicate("10abc")); // php
        $this->assertFalse($predicate("abc"));
    }

    public function testSamePredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\same(10);

        $this->assertTrue($predicate(10));
        $this->assertFalse($predicate("10"));
        $this->assertFalse($predicate("10abc"));
        $this->assertFalse($predicate("abc"));
    }

    public function testInstancePredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\instance(Foo::class);

        $this->assertTrue($predicate(new Foo));
        $this->assertFalse($predicate(new Bar));
    }

    public function testConstantPredicate()
    {
        $false = \Kadet\Xmpp\Utils\filter\constant(false);
        $true = \Kadet\Xmpp\Utils\filter\constant(true);

        $this->assertTrue($true("what", "fucking", "ever", 21.37));
        $this->assertFalse($false("omg", "lol"));
    }

    public function testAnyPredicate()
    {
        $false = \Kadet\Xmpp\Utils\filter\constant(false);
        $true = \Kadet\Xmpp\Utils\filter\constant(true);

        $this->assertFalse((\Kadet\Xmpp\Utils\filter\any($false, $false))());
        $this->assertTrue((\Kadet\Xmpp\Utils\filter\any($true, $false))());
        $this->assertTrue((\Kadet\Xmpp\Utils\filter\any($false, $true))());
    }

    public function testAnyPredicateWithArguments()
    {
        $foo = \Kadet\Xmpp\Utils\filter\instance(Foo::class);
        $bar = \Kadet\Xmpp\Utils\filter\instance(Bar::class);

        $predicate = \Kadet\Xmpp\Utils\filter\any($foo, $bar);

        $this->assertTrue($predicate(new Foo));
        $this->assertTrue($predicate(new Bar));
        $this->assertFalse($predicate(new Lel));
    }

    public function testAllPredicate()
    {
        $false = \Kadet\Xmpp\Utils\filter\constant(false);
        $true = \Kadet\Xmpp\Utils\filter\constant(true);

        $this->assertFalse((\Kadet\Xmpp\Utils\filter\all($false, $false))());
        $this->assertFalse((\Kadet\Xmpp\Utils\filter\all($false, $true))());
        $this->assertTrue((\Kadet\Xmpp\Utils\filter\all($true, $true))());
    }

    public function testAllPredicateWithArguments()
    {
        $lel = \Kadet\Xmpp\Utils\filter\instance(Lel::class);
        $kek = \Kadet\Xmpp\Utils\filter\instance(Kek::class);

        $predicate = \Kadet\Xmpp\Utils\filter\all($lel, $kek);

        $this->assertFalse($predicate(new Foo));
        $this->assertFalse($predicate(new Bar));
        $this->assertTrue($predicate(new Lel));
    }

    public function testNotPredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\not(\Kadet\Xmpp\Utils\filter\constant(true));

        $this->assertFalse($predicate());
    }

    public function testPassPredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\pass();

        $this->assertTrue($predicate());
    }

    public function testFailPredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\fail();

        $this->assertFalse($predicate());
    }

    public function testMatchesPredicate()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\matches('~^https?://~');

        $this->assertTrue($predicate('http://google.pl'));
        $this->assertTrue($predicate('https://google.pl'));
        $this->assertFalse($predicate('google.pl'));
    }

    /**
     * @dataProvider argumentProvider
     * @param $predicate
     */
    public function testArgumentBinding($predicate)
    {
        $this->assertTrue($predicate(1, 2, 3, 4));
    }

    public function testConsecutive()
    {
        $predicate = \Kadet\Xmpp\Utils\filter\consecutive(pass(), pass(), equals(3));
        $this->assertTrue($predicate('foo', 'bar', 3));
        $this->assertFalse($predicate('foo', 3, 'bar'));
    }

    public function argumentProvider()
    {
        return [
            'one'  => [\Kadet\Xmpp\Utils\filter\argument(1, function (...$arguments) {
                return $arguments === [2];
            })],
            'two'  => [\Kadet\Xmpp\Utils\filter\argument(1, function (...$arguments) {
                return $arguments === [2, 3];
            }, 2)],
            'many' => [\Kadet\Xmpp\Utils\filter\argument(1, function (...$arguments) {
                return $arguments === [2, 3, 4];
            }, false)],
        ];
    }
}
