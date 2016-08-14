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


use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Module\Binding;
use Kadet\Xmpp\Stanza\Stanza;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

/**
 * @covers Kadet\Xmpp\Module\Binding
 */
class BindingTest extends \PHPUnit_Framework_TestCase
{
    public function testBindingInitiationWithResource()
    {
        $features = new Features([
            new XmlElement('bind', 'urn:ietf:params:xml:ns:xmpp-bind')
        ]);

        $client = $this->getMockClient('local@domain.tld/resource');
        $client->expects($this->once())->method('write')->with($this->callback(function (Stanza $element) use (&$id) {
            $id = $element->id;

            $this->assertEquals('jabber:client', $element->namespace);
            $this->assertEquals('iq', $element->name);
            $this->assertEquals('set', $element->getAttribute('type'));
            $this->assertTrue($element->has(\Kadet\Xmpp\Utils\filter\element('bind', 'urn:ietf:params:xml:ns:xmpp-bind')));
            $bind = $element->element('bind', 'urn:ietf:params:xml:ns:xmpp-bind');
            $this->assertTrue($bind->has(\Kadet\Xmpp\Utils\filter\tag('resource')));
            $this->assertEquals('resource', $bind->element('resource')->innerXml);

            return true;
        }));
        $client->emit('features', [$features]);

        $this->handleResponse($client, $id, 'resource');
    }

    public function testFeaturesWithoutBinding()
    {
        $features = new Features();

        $client = $this->getMockClient('local@domain.tld/resource');
        $client->expects($this->never())->method('write');
        $client->expects($this->never())->method('bind');

        $client->emit('features', [$features]);
    }

    public function testBindingInitiationWithoutResource()
    {
        $features = new Features([
            new XmlElement('bind', 'urn:ietf:params:xml:ns:xmpp-bind')
        ]);

        $client = $this->getMockClient('local@domain.tld');
        $client->expects($this->once())->method('write')->with($this->callback(function (Stanza $element) use (&$id) {
            $id = $element->id;

            $this->assertEquals('jabber:client', $element->namespace);
            $this->assertEquals('iq', $element->name);
            $this->assertEquals('set', $element->getAttribute('type'));
            $this->assertTrue($element->has(\Kadet\Xmpp\Utils\filter\element('bind', 'urn:ietf:params:xml:ns:xmpp-bind')));
            $bind = $element->element('bind', 'urn:ietf:params:xml:ns:xmpp-bind');
            $this->assertFalse($bind->has(\Kadet\Xmpp\Utils\filter\tag('resource')));

            return true;
        }));
        $client->emit('features', [$features]);

        $this->handleResponse($client, $id);
    }

    public function handleResponse(XmppClient $client, $id, $resource = 'generated')
    {
        $jid = "local@domain.tld/$resource";
        $result = new Stanza('iq', ['type' => 'result', 'id' => $id], [
            new XmlElement('bind', 'urn:ietf:params:xml:ns:xmpp-bind', [
                new XmlElement('jid', null, $jid)
            ])
        ]);

        $client->expects($this->once())->method('bind')->with($jid);
        $client->emit('element', [ $result ]);
    }

    public function getMockClient($jid)
    {
        /** @var XmppClient $client */
        $client = $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid($jid), [
                'connector' => new ConnectorStub()
            ]])->setMethods(['write', 'bind'])
            ->getMock();

        $client->register(new Binding());

        return $client;
    }
}
