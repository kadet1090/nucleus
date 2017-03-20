<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2017, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Tests\Stanza;


use Kadet\Xmpp\Stanza\Presence;
use function \Kadet\Xmpp\Utils\filter\element\name;
/**
 * Class PresenceTest
 * @package Kadet\Xmpp\Tests\Stanza
 *
 * @covers \Kadet\Xmpp\Stanza\Presence
 */
class PresenceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Presence
     */
    private $_presence;

    protected function setUp()
    {
         $this->_presence = new Presence();
    }

    public function testBasics()
    {
        $this->assertEquals('presence', $this->_presence->localName);
        $this->assertEquals('jabber:client', $this->_presence->namespace);
    }

    /**
     * @param $show
     * @dataProvider showProvider
     */
    public function testShowSetting($show)
    {
        $this->_presence->show = $show;

        $this->assertEquals($show, $this->_presence->show);
        $this->assertEquals($show, $this->_presence->get(name('show'))->innerXml);
    }

    public function testShowAvailable()
    {
        $this->_presence->show = 'available';

        $this->assertEquals('available', $this->_presence->type);
        $this->assertNull($this->_presence->get(name('show')));
    }

    public function testShowUnavailable()
    {
        $this->_presence->show = 'unavailable';

        $this->assertEquals('unavailable', $this->_presence->type);
        $this->assertNull($this->_presence->get(name('show')));
    }

    public function testStatus()
    {
        $status = 'lorem ipsum dolor sit amet';

        $this->_presence->status = $status;
        $this->assertEquals($status, $this->_presence->status);
        $this->assertEquals($status, $this->_presence->get(name('status'))->innerXml);
    }

    public function testPriority()
    {
        $priority = 10;

        $this->_presence->priority = 10;
        $this->assertEquals($priority, $this->_presence->priority);
        $this->assertEquals($priority, $this->_presence->get(name('priority'))->innerXml);
    }

    public function showProvider() {
        return array_map(function($show) {
            return [ $show ];
        }, [ 'chat', 'away', 'xa', 'dnd' ]);
    }
}
