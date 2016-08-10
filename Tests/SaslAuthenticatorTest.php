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


use Fabiang\Sasl\Authentication\AuthenticationInterface;
use Fabiang\Sasl\Sasl;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Module\SaslAuthenticator;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

/**
 * Class SaslAuthenticatorTest
 * @package Kadet\Xmpp\Tests
 *
 * @covers Kadet\Xmpp\Module\SaslAuthenticator
 */
class SaslAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    public function testMechanismWithoutChallenge()
    {
        $features = new Features([
            new XmlElement('mechanisms', 'urn:ietf:params:xml:ns:xmpp-sasl', [
                new XmlElement('mechanism', null, 'STUB')
            ])
        ]);

        $mechanism = $this->getMockForAbstractClass(AuthenticationInterface::class, [], 'StubAuthenticator');
        $mechanism->expects($this->once())->method('createResponse')->willReturn('foobar');

        $factory = $this->getSaslFactoryMock();
        $factory->expects($this->atLeast(1))->method('factory')->with('STUB', $this->anything())->willReturn($mechanism);

        $client = $this->getMockClient('local@domain.tld', 'password', $factory);
        $client->expects($this->once())->method('write')->with($this->callback(function (XmlElement $element) {
            $this->assertEquals('auth', $element->localName);
            $this->assertEquals('STUB', $element->getAttribute('mechanism'));
            $this->assertEquals('foobar', base64_decode($element->innerXml));

            return true;
        }));

        $this->assertTrue($client->get(SaslAuthenticator::class)->auth($features));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Sasl
     */
    private function getSaslFactoryMock()
    {
        return $this->getMockBuilder(Sasl::class)
            ->disableAutoload()
            ->disableProxyingToOriginalMethods()
            ->setMethods(['factory'])
            ->getMock();
    }

    /**
     * @param        $jid
     * @param string $password
     * @param Sasl   $factory
     * @return XmppClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockClient($jid, $password = 'password', Sasl $factory)
    {
        return $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([$jid instanceof Jid ? $jid : new Jid($jid), [
                'connector' => new ConnectorStub(),
                'modules' => [
                    new SaslAuthenticator($password, $factory)
                ]
            ]])->setMethods(['write', 'bind'])
            ->getMock();
    }
}
