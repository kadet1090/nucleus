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


use Fabiang\Sasl\Authentication\AuthenticationInterface;
use Fabiang\Sasl\Authentication\ChallengeAuthenticationInterface;
use Fabiang\Sasl\Exception\InvalidArgumentException;
use Fabiang\Sasl\Sasl;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Component\SaslAuthenticator;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Tests\Stubs\ConnectorStub;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

/**
 * Class SaslAuthenticatorTest
 * @package Kadet\Xmpp\Tests
 *
 * @covers \Kadet\Xmpp\Component\SaslAuthenticator
 */
class SaslAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|XmppClient */
    private $_client;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_client = $this->getMockClient();
    }

    public function testWithChallengeSuccess()
    {
        $this->mechanismWithChallenge();
        $this->success();
    }

    public function testWithoutChallengeSuccess()
    {
        $this->mechanismWithoutChallenge();
        $this->success();
    }

    /** @expectedException \Kadet\Xmpp\Exception\Protocol\AuthenticationException */
    public function testWithChallengeFailure()
    {
        $this->mechanismWithChallenge();
        $this->failure();
    }

    /** @expectedException \Kadet\Xmpp\Exception\Protocol\AuthenticationException */
    public function testWithoutChallengeFailure()
    {
        $this->mechanismWithoutChallenge();
        $this->failure();
    }

    private function mechanismWithoutChallenge()
    {
        $features = new Features([
            new XmlElement('mechanisms', 'urn:ietf:params:xml:ns:xmpp-sasl', [
                'content' => new XmlElement('mechanism', null, ['content' => 'STUB'])
            ])
        ]);

        $mechanism = $this->getMockForAbstractClass(AuthenticationInterface::class, [], 'StubAuthenticator');
        $mechanism->expects($this->once())->method('createResponse')->willReturn('foobar');

        $factory = $this->getSaslFactoryMock($mechanism);

        $this->_client->register(new SaslAuthenticator('password', $factory));
        $this->_client->expects($this->once())->method('write')->with($this->callback(function (XmlElement $element) {
            $this->assertEquals('auth', $element->localName);
            $this->assertEquals('STUB', $element->getAttribute('mechanism'));
            $this->assertEquals('foobar', base64_decode($element->innerXml));

            return true;
        }));

        $this->assertTrue($this->_client->get(SaslAuthenticator::class)->auth($features));
    }

    private function mechanismWithChallenge()
    {
        $features = new Features([
            new XmlElement('mechanisms', 'urn:ietf:params:xml:ns:xmpp-sasl', [
                'content' => new XmlElement('mechanism', null, ['content' => 'STUB'])
            ])
        ]);

        $mechanism = $this->getMockForAbstractClass(ChallengeAuthenticationInterface::class, [], 'ChallengeStubAuthenticator');
        $mechanism->expects($this->exactly(2))->method('createResponse')
            ->withConsecutive([], ['challenge'])
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new InvalidArgumentException()),
                $this->returnValue('response')
            );

        $factory = $this->getSaslFactoryMock($mechanism);

        $this->_client->register(new SaslAuthenticator('password', $factory));
        $this->_client->expects($this->exactly(2))->method('write')->withConsecutive($this->callback(function (XmlElement $element) {
            $this->assertEquals('auth', $element->localName);
            $this->assertEquals('STUB', $element->getAttribute('mechanism'));
            $this->assertEquals('=', $element->innerXml);

            return true;
        }), $this->callback(function (XmlElement $element) {
            $this->assertEquals('response', $element->localName);
            $this->assertEquals('response', base64_decode($element->innerXml));

            return true;
        }));

        $this->assertTrue($this->_client->get(SaslAuthenticator::class)->auth($features));
        $this->_client->emit('element', [
            new XmlElement('challenge', 'urn:ietf:params:xml:ns:xmpp-sasl', ['content' => base64_encode('challenge')])
        ]);
    }

    private function success()
    {
        $proceed = new XmlElement('success', 'urn:ietf:params:xml:ns:xmpp-sasl');

        $this->_client->expects($this->once())->method('restart');
        $this->_client->emit('element', [$proceed]);
    }

    private function failure()
    {
        $proceed = new XmlElement('failure', 'urn:ietf:params:xml:ns:xmpp-sasl');

        $this->_client->emit('element', [$proceed]);
    }

    /**
     * @param $mechanism
     * @return Sasl|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSaslFactoryMock($mechanism)
    {
        $factory = $this->getMockBuilder(Sasl::class)
            ->disableAutoload()
            ->disableProxyingToOriginalMethods()
            ->setMethods(['factory'])
            ->getMock();

        $factory->expects($this->atLeast(1))->method('factory')->with('STUB', $this->callback(function($options) {
            return $options['secret'] == 'password'
            && $options['authcid'] == 'local'
            && $options['hostname'] == 'domain.tld';
        }))->willReturn($mechanism);

        return $factory;
    }

    /**
     * @return XmppClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockClient()
    {
        return $this->getMockBuilder(XmppClient::class)
            ->setConstructorArgs([new Jid('local@domain.tld'), [
                'connector' => new ConnectorStub(),
                'default-modules' => false
            ]])->setMethods(['write', 'bind', 'restart'])
            ->getMock();
    }
}
