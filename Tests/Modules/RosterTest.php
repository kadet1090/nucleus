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

namespace Kadet\Xmpp\Tests\Modules;


use Kadet\Xmpp\Component\Roster;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use function Kadet\Xmpp\Utils\filter\all;
use Kadet\Xmpp\XmppClient;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use \Kadet\Xmpp\Utils\filter as with;

/**
 * Class RosterTest
 * @package Kadet\Xmpp\Tests\Modules
 * @covers \Kadet\Xmpp\Component\Roster
 */
class RosterTest extends \PHPUnit_Framework_TestCase
{
    /** @var XmppClient|Mock */
    private $_client;
    /** @var Roster */
    private $_roster;

    public function setUp()
    {
        $this->_client = $this->getMockClient();
    }

    public function testRequestingRosterOnInitialization()
    {
        $this->_client->expects($this->once())->method('write')->with($this->callback(all(
            with\stanza\type('get'),
            with\iq\query(with\element('query', 'jabber:iq:roster'))
        )));

        $this->_client->emit('init', [ new \SplQueue() ]);
    }

    public function testReceivingItemsFromResult()
    {
        $iq = new Iq('result', ['query' => new Iq\Query\Roster([
            'items' => [
                $foo = new Iq\Query\Roster\Item(new Jid('foo@local')),
                $goo = new Iq\Query\Roster\Item(new Jid('goo@local'), ['name' => 'named goo']),
                $bar = new Iq\Query\Roster\Item(new Jid('bar@local'), [
                    'name' => 'Foo Barovsky',
                    'groups' => ['stubs']
                ]),
            ]
        ])]);
        $this->_client->emit('element', [ $iq ]);

        $this->assertEquals([
            'foo@local' => clone $foo,
            'goo@local' => clone $goo,
            'bar@local' => clone $bar
        ], $this->_roster->items);
    }

    public function testRemovingItemFromRosterByJid()
    {
        $this->givenDefaultItems();

        $this->_client->expects($this->once())->method('write')->with($this->callback(function(Iq $iq) {
            $this->assertInstanceOf(Iq\Query\Roster::class, $iq->query);
            /** @var Iq\Query\Roster $query */
            $query = $iq->query;
            $this->assertCount(1, $query->items);

            $this->assertEquals('foo@domain', (string)$query->items[0]->jid);
            $this->assertEquals('remove', (string)$query->items[0]->subscription);

            return true;
        }));

        $this->_roster->remove(new Jid('foo@domain'));
    }

    /**
     * @return XmppClient|Mock
     */
    public function getMockClient()
    {
        /** @var XmppClient $client */
        $client = $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain'), [
                'connector' => new ConnectorStub(),
                'default-modules' => false
            ]])->setMethods(['write'])
            ->getMock();

        $client->register($this->_roster = new Roster());
        return $client;
    }

    private function givenDefaultItems()
    {
        $this->_client->emit('element', [new Iq('result', ['query' => new Iq\Query\Roster([
            'items' => [
                new Iq\Query\Roster\Item(new Jid('foo@domain')),
                new Iq\Query\Roster\Item(new Jid('goo@domain'), ['name' => 'named goo']),
                new Iq\Query\Roster\Item(new Jid('bar@domain'), [
                    'name' => 'Foo Barovsky',
                    'groups' => ['stubs']
                ]),
            ]
        ])])]);
    }
}
