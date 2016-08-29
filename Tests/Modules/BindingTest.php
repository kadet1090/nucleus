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


use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Component\Binding;
use Kadet\Xmpp\Stanza\Error;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Stanza\Stanza;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Kadet\Xmpp\Utils\filter as with;

/**
 * @covers \Kadet\Xmpp\Component\Binding
 */
class BindingTest extends \PHPUnit_Framework_TestCase
{
    /** @var XmppClient|Mock */
    private $_client;

    public function testFeaturesWithoutBinding()
    {
        $features = new Features();

        $client = $this->getMockClient('local@domain.tld/resource');
        $client->expects($this->never())->method('write');
        $client->expects($this->never())->method('bind');

        $client->emit('features', [$features]);
    }

    public function binding($resource = null)
    {
        $features = new Features([
            new XmlElement('bind', 'urn:ietf:params:xml:ns:xmpp-bind')
        ]);

        $this->_client = $this->getMockClient('local@domain.tld' . ($resource ? '/'.$resource : null));
        $this->_client->expects($this->once())->method('write')->with($this->callback(function (Stanza $element) use (&$id, $resource) {
            $id = $element->id;

            $this->assertEquals('jabber:client', $element->namespace);
            $this->assertEquals('iq', $element->name);
            $this->assertEquals('set', $element->getAttribute('type'));
            $this->assertTrue($element->has(with\element('bind', 'urn:ietf:params:xml:ns:xmpp-bind')));
            $bind = $element->element('bind', 'urn:ietf:params:xml:ns:xmpp-bind');

            if($resource) {
                $this->assertTrue($bind->has(with\element\name('resource')));
                $this->assertEquals('resource', $bind->element('resource')->innerXml);
            }

            return true;
        }));
        $this->_client->emit('features', [$features]);

        return $id;
    }

    public function testBindingInitiationWithoutResourceSuccess()
    {
        $this->success($this->binding());
    }

    public function testBindingInitiationWithResourceSuccess()
    {
        $this->success($this->binding('resource'), 'resource');
    }

    /**
     * @expectedException \Kadet\Xmpp\Exception\Protocol\BindingException
     * @expectedExceptionMessageRegExp /Bad Request:/i
     */
    public function testBindingInitiationWithoutResourceFailure()
    {
        $this->failure($this->binding(), new Error('bad-request'));
    }

    /**
     * @expectedException \Kadet\Xmpp\Exception\Protocol\BindingException
     * @expectedExceptionMessageRegExp /Conflict:/i
     */
    public function testBindingInitiationWithResourceFailure()
    {
        $this->failure($this->binding('resource'), new Error('conflict'));
    }

    public function success($id, $resource = 'generated')
    {
        $jid = "local@domain.tld/$resource";
        $result = new Iq([
            'type' => 'result',
            'id'   => $id,
            'query' => new Iq\Query('urn:ietf:params:xml:ns:xmpp-bind', 'bind', [
                'content' => new XmlElement('jid', null, ['content' => $jid])
            ])
        ]);

        $this->_client->expects($this->once())->method('bind')->with($jid);
        $this->_client->emit('element', [ $result ]);
    }

    public function failure($id, Error $error)
    {
        $result = new Iq(['type' => 'error', 'id' => $id, 'content' => $error]);
        $this->_client->emit('element', [ $result ]);
    }

    /**
     * @param $jid
     * @return XmppClient|Mock
     */
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
