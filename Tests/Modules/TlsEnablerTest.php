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
use Kadet\Xmpp\Component\TlsEnabler;
use Kadet\Xmpp\Network\TcpStream;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;
use React\Stream\DuplexStreamInterface;
use React\Stream\Stream;

/**
 * Class StartTlsTest
 * @package Kadet\Xmpp\Tests\Modules
 *
 * @covers \Kadet\Xmpp\Component\TlsEnabler
 */
class TlsEnablerTest extends \PHPUnit_Framework_TestCase
{
    /** @var XmppClient|\PHPUnit_Framework_MockObject_MockObject */
    private $_client;

    /** @var DuplexStreamInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $_stream;

    public function testNotRequiredEncryption()
    {
        $this->setUpClientWithEncryption();
        $features = new Features([
            new Features\StartTls()
        ]);

        $this->checkEncryptionEnabling($features);
    }

    public function testRequiredEncryption()
    {
        $this->setUpClientWithEncryption();
        $tls = new Features\StartTls();
        $tls->append(new XmlElement('required'));

        $features = new Features([ $tls ]);

        $this->checkEncryptionEnabling($features);
    }

    public function testNotRequiredWithoutEncryption()
    {
        $this->setUpClientWithoutEncryption();
        $features = new Features([
            new Features\StartTls()
        ]);

        $this->_client->expects($this->never())->method('write');

        $this->assertFalse($this->_client->get(TlsEnabler::class)->startTls($features));
    }

    /**
     * @expectedException \Kadet\Xmpp\Exception\Protocol\TlsException
     */
    public function testRequiredWithoutEncryption()
    {
        $this->setUpClientWithoutEncryption();

        $tls = new Features\StartTls();
        $tls->append(new XmlElement('required'));

        $features = new Features([ $tls ]);

        $this->_client->get(TlsEnabler::class)->startTls($features);
    }

    protected function setUpClientWithEncryption()
    {
        $this->_stream = $this->getMockBuilder(TcpStream::class)
            ->disableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->setMethods(['encrypt'])
            ->getMock();

        $this->setUpClient();
    }

    protected function setUpClientWithoutEncryption()
    {
        $this->_stream = $this->getMockBuilder(Stream::class)
            ->disableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->getMock();

        $this->setUpClient();
    }

    protected function setUpClient()
    {
        /** @var XmppClient $client */
        $this->_client = $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain.tld'), [
                'connector'       => new ConnectorStub($this->_stream),
                'default-modules' => false
            ]])
            ->setMethods(['write'])
            ->getMock();

        $this->_client->register(new TlsEnabler());
        $this->_client->connect();
    }

    /**
     * @param $features
     */
    protected function checkEncryptionEnabling($features)
    {
        $this->_client->expects($this->at(0))->method('write')->with($this->callback(function (XmlElement $element) {
            $this->assertEquals('starttls', $element->localName);
            $this->assertEquals('urn:ietf:params:xml:ns:xmpp-tls', $element->namespace);

            $this->_client->emit('element', [new XmlElement('proceed', 'urn:ietf:params:xml:ns:xmpp-tls')]);

            return true;
        }));
        $this->_stream->expects($this->once())->method('encrypt');

        $this->assertTrue($this->_client->get(TlsEnabler::class)->startTls($features));
    }
}
