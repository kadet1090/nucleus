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

namespace Kadet\Xmpp\Tests\Modules;


use Kadet\Xmpp\Component\PingKeepAlive;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Stanza\Iq\Query;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\XmppClient;
use React\EventLoop\LoopInterface;

use \Kadet\Xmpp\Utils\filter as predicate;

class PingKeepAliveTest extends \PHPUnit_Framework_TestCase
{
    public function testKeepAliveTimer()
    {
        /** @var LoopInterface|\PHPUnit_Framework_MockObject_MockObject $loop */
        $loop = $this->getMockBuilder(LoopInterface::class)->setMethods(['addPeriodicTimer'])->getMockForAbstractClass();
        $loop->expects($this->once())->method('addPeriodicTimer')->withConsecutive($this->greaterThan(1.0), $this->callback(function($callback) {
            return is_callable($callback);
        }));

        $this->getMockClient($loop)->emit('state', ['ready']);
    }

    public function testNoKeepAliveTimer()
    {
        /** @var LoopInterface|\PHPUnit_Framework_MockObject_MockObject $loop */
        $loop = $this->getMockBuilder(LoopInterface::class)->setMethods(['addPeriodicTimer'])->getMockForAbstractClass();
        $loop->expects($this->never())->method('addPeriodicTimer');

        $this
            ->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain.tld'), [
                'connector' => new ConnectorStub(null, $loop),
                'default-modules' => false,
                'modules' => [
                    new PingKeepAlive(false)
                ]
            ]])
            ->getMock()
            ->emit('state', ['ready']);
    }

    public function testKeepAliveTick()
    {
        /** @var LoopInterface|\PHPUnit_Framework_MockObject_MockObject $loop */
        $loop = $this->getMockBuilder(LoopInterface::class)->setMethods(['addPeriodicTimer'])->getMockForAbstractClass();

        /** @var callable $tick */
        $tick = null;
        $loop->method('addPeriodicTimer')->willReturnCallback(function($_, $callback) use(&$tick) {
            $tick = $callback;
        });

        $client = $this->getMockClient($loop);
        $client->expects($this->once())->method('write')->withConsecutive($this->callback(
            \Kadet\Xmpp\Utils\filter\iq\query(\Kadet\Xmpp\Utils\filter\element('ping', 'urn:xmpp:ping'))
        ));

        $client->emit('state', ['ready']);

        $tick();
    }

    public function testPingResponse()
    {
        $iq = new Iq('get', ['id' => 'testme', 'query' => new Query('urn:xmpp:ping', 'ping')]);

        $client = $this->getMockClient();
        $client->expects($this->once())->method('write')->withConsecutive(predicate\all(
            predicate\stanza\type('result'),
            predicate\stanza\id('testme'),
            predicate\iq\query(\Kadet\Xmpp\Utils\filter\element('ping', 'urn:xmpp:ping'))
        ));

        $client->emit('element', [ $iq ]);
    }

    public function testNotRespondingOnResult()
    {
        $iq = new Iq('result', ['id' => 'testme', 'query' => new Query('urn:xmpp:ping', 'ping')]);

        $client = $this->getMockClient();
        $client->expects($this->never())->method('write');
        $client->emit('element', [ $iq ]);
    }

    /**
     * @param LoopInterface $loop
     * @return XmppClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockClient(LoopInterface $loop = null)
    {
        return $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain.tld'), [
                'connector' => new ConnectorStub(null, $loop),
                'default-modules' => false,
                'modules' => [
                    new PingKeepAlive()
                ]
            ]])->setMethods(['write'])
            ->getMock();
    }
}
