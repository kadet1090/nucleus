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

namespace Kadet\Xmpp\Tests;

use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Exception\InvalidArgumentException;

class JidTest extends \PHPUnit_Framework_TestCase
{
    public function validJidProvider()
    {
        return [
            'domain-part only'          => ['hostname.tld'],
            'bare jid'                  => ['hostname.tld', 'local-part'],
            'full jid'                  => ['hostname.tld', 'local-part', 'resource'],
            'domain-part with resource' => ['hostname.tld', null, 'resource'],

            'bare jid from string' => ['local-part@hostname.tld'],
            'full jid from string' => ['local-part@hostname.tld/resource'],
        ];
    }

    public function invalidJidProvider()
    {
        return [
            [''],
            ['local-part@'],
            ['local-part@/resource'],
            ['/resource'],

            ['', 'local-part'],
            ['', 'local-part', 'resource'],
            ['', null, 'resource'],

            ['<'],
            ['hostname.tld', '<'],
            ['hostname.tld', '<', '>'],
            ['hostname.tld', null, '>'],
        ];
    }

    public function testDomainWithResource()
    {
        $address = new Jid('hostname.tld', null, 'resource');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('resource', $address->resource);
    }

    public function testDomainWithResourceFromString()
    {
        $address = new Jid('hostname.tld/resource');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('resource', $address->resource);
    }

    public function testBareCreation()
    {
        $address = new Jid('hostname.tld', 'local-part');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('local-part', $address->local);
    }

    public function testBareCreationFromString()
    {
        $address = new Jid('local-part@hostname.tld');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('local-part', $address->local);
    }

    public function testFull()
    {
        $address = new Jid('hostname.tld', 'local-part', 'resource');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('local-part', $address->local);
        $this->assertEquals('resource', $address->resource);
    }

    public function testFullFromString()
    {
        $address = new Jid('local-part@hostname.tld/resource');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertEquals('local-part', $address->local);
        $this->assertEquals('resource', $address->resource);
    }

    public function testHostnameOnly()
    {
        $address = new Jid('hostname.tld');
        $this->assertEquals('hostname.tld', $address->domain);
        $this->assertNull($address->resource);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Domain-part of JID is REQUIRED/i
     */
    public function testEmptyHostname()
    {
        new Jid('');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Domain-part of JID is REQUIRED/i
     */
    public function testEmptyHostnameFromString()
    {
        new Jid('local-part@/resource');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Domain-part of JID contains not allowed character '.'/i
     */
    public function testInvalidHostname()
    {
        new Jid('<invalid>');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Resource-part of JID contains not allowed character '.'/i
     */
    public function testInvalidResource()
    {
        new Jid('hostname.tld', null, '<invalid>');
    }

    /**
     * @dataProvider validJidProvider
     */
    public function testIsValid(...$arguments)
    {
        $this->assertTrue(Jid::isValid(...$arguments));
    }

    /**
     * @dataProvider invalidJidProvider
     */
    public function testIsNotValid(...$arguments)
    {
        $this->assertFalse(Jid::isValid(...$arguments));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Domain-part of JID contains not allowed character '.'/i
     */
    public function testValidationInvalidHostname()
    {
        Jid::validate('<invalid>');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Resource-part of JID contains not allowed character '.'/i
     */
    public function testValidationInvalidResource()
    {
        Jid::validate('hostname.tld', null, '<invalid>');
    }

    public function testIsBare()
    {
        $this->assertTrue((new Jid('local-part@hostname.tld'))->isBare());
        $this->assertTrue((new Jid('hostname.tld'))->isBare());

        $this->assertFalse((new Jid('local-part@hostname.tld/resource'))->isBare());
        $this->assertFalse((new Jid('hostname.tld/resource'))->isBare());
    }

    public function testBare()
    {
        $bare = (new Jid('local-part@hostname.tld/resource'))->bare();
        $this->assertEquals('hostname.tld', $bare->domain);
        $this->assertEquals('local-part', $bare->local);
        $this->assertNull($bare->resource);
    }

    public function testIsFull()
    {
        $this->assertTrue((new Jid('local-part@hostname.tld/resource'))->isFull());

        $this->assertFalse((new Jid('local-part@hostname.tld'))->isFull());
        $this->assertFalse((new Jid('hostname.tld'))->isFull());
        $this->assertFalse((new Jid('hostname.tld/resource'))->isFull());
    }

    public function testToString()
    {
        $this->assertEquals('hostname.tld', (string)(new Jid('hostname.tld')));
        $this->assertEquals('hostname.tld/resource', (string)(new Jid('hostname.tld/resource')));
        $this->assertEquals('local-part@hostname.tld/resource', (string)(new Jid('local-part@hostname.tld/resource')));
        $this->assertEquals('local-part@hostname.tld', (string)(new Jid('local-part@hostname.tld')));
    }
}
