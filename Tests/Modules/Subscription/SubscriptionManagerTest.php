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

namespace Kadet\Xmpp\Tests\Modules\Subscription;


use Kadet\Xmpp\Component\Subscription\SubscriptionManager;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Presence;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\XmppClient;
use PHPUnit_Framework_MockObject_MockObject as Mock;

use function Kadet\Xmpp\Utils\filter\{
    all, not, pass
};
use function Kadet\Xmpp\Utils\filter\stanza\{
    to, type, id
};

class SubscriptionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmppClient|Mock
     */
    private $_client;

    /**
     * @var SubscriptionManager
     */
    private $_manager;

    /**
     * @return XmppClient|Mock
     */
    public function getMockClient()
    {
        return $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain'), [
                'connector' => new ConnectorStub(),
                'default-modules' => false,
                'modules' => [
                    $this->_manager = new SubscriptionManager()
                ]
            ]])->setMethods(['write'])
            ->getMock();
    }

    protected function setUp()
    {
        $this->_client = $this->getMockClient();
    }

    public function testSubscriptionRequest()
    {
        $jid = new Jid('foo@example.net');

        $this->_client->expects($this->once())->method('write')->withConsecutive($this->callback(all(
            type('subscribe'), id(not(null)), to((string)$jid->bare())
        )));

        $this->_manager->subscribe($jid);
    }


    public function testSubscriptionRequestEvent()
    {
        $jid = new Jid('foo@example.net');

        $presence = new Presence(['type' => 'subscribe', 'from' => $jid]);

        $mock = $this->getMockBuilder('stdClass')->setMethods(['predicate'])->getMock();
        $mock->expects($this->once())->method('predicate')->withConsecutive($presence);

        $this->_manager->on('request', [$mock, 'predicate']);
        $this->_client->emit('element', [ $presence ]);
    }

    public function testSubscriptionRemovalRequest()
    {
        $jid = new Jid('foo@example.net');

        $this->_client->expects($this->once())->method('write')->withConsecutive($this->callback(all(
            type('unsubscribe'), id(not(null)), to((string)$jid->bare())
        )));

        $this->_manager->unsubscribe($jid);
    }


    public function testSubscriptionCancellation()
    {
        $jid = new Jid('foo@example.net');

        $this->_client->expects($this->once())->method('write')->withConsecutive($this->callback(all(
            type('unsubscribed'), id(not(null)), to((string)$jid->bare())
        )));

        $this->_manager->cancel($jid);
    }
}
