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

namespace Kadet\Xmpp\Module;

use Fabiang\Sasl\Authentication\AuthenticationInterface;
use Fabiang\Sasl\Authentication\ChallengeAuthenticationInterface;
use Fabiang\Sasl\Exception\InvalidArgumentException;
use Fabiang\Sasl\Sasl;
use Kadet\Xmpp\Exception\Protocol\AuthenticationException;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

use Kadet\Xmpp\Utils\filter as with;
use function Kadet\Xmpp\Utils\filter\{
    all, in
};

class SaslAuthenticator extends ClientModule implements Authenticator
{
    const XMLNS = 'urn:ietf:params:xml:ns:xmpp-sasl';

    /**
     * Client's password used in authorisation.
     *
     * @var string
     */
    private $_password;

    /**
     * Factory used to create mechanisms
     *
     * @var Sasl
     */
    private $_sasl;

    /**
     * Authentication constructor.
     *
     * @param string $password Client's password
     * @param Sasl   $sasl     Factory used to create mechanisms
     */
    public function __construct($password = null, Sasl $sasl = null)
    {
        $this->setPassword($password);
        $this->_sasl = $sasl ?: new Sasl();
    }

    public function setClient(XmppClient $client)
    {
        parent::setClient($client);

        $client->on('features', function (Features $features) {
            return !$this->auth($features);
        });
    }

    public function auth(Features $features)
    {
        if (!empty($features->mechanisms)) {
            foreach ($features->mechanisms as $name) {
                if($this->tryMechanism($name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function tryMechanism($name) {
        try {
            $mechanism = $this->_sasl->factory($name, [
                'authcid'  => $this->_client->jid->local,
                'secret'   => $this->_password,
                'hostname' => $this->_client->jid->domain,
                'service'  => 'xmpp'
            ]);

            $this->_client->getLogger()->debug('Starting auth using {name} mechanism.', ['name' => $name]);

            $auth = new XmlElement('auth', self::XMLNS);
            $auth->setAttribute('mechanism', $name);

            $auth->append(base64_encode(
                $mechanism instanceof ChallengeAuthenticationInterface
                    ? $this->mechanismWithChallenge($mechanism)
                    : $this->mechanismWithoutChallenge($mechanism)
            ));

            $this->_client->write($auth);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    private function mechanismWithChallenge(ChallengeAuthenticationInterface $mechanism) {
        try {
            $response = base64_encode($mechanism->createResponse());
        } catch (InvalidArgumentException $e) {
            $response = '=';
        }

        $callback = $this->_client->on('element', function (XmlElement $challenge) use ($mechanism) {
            $this->handleChallenge($challenge, $mechanism);
        }, with\element('challenge', self::XMLNS));

        $this->_client->once('element', function (XmlElement $result) use ($callback) {
            $this->_client->removeListener('element', $callback);
            $this->handleAuthResult($result);
        }, $this->_resultPredicate());

        return $response;
    }

    private function mechanismWithoutChallenge(AuthenticationInterface $mechanism)
    {
        $this->_client->once('element', function (XmlElement $result) {
            $this->handleAuthResult($result);
        }, $this->_resultPredicate());

        return $mechanism->createResponse();
    }

    private function handleChallenge(XmlElement $challenge, AuthenticationInterface $mechanism)
    {
        $response = new XmlElement('response', self::XMLNS);
        $response->append(base64_encode($mechanism->createResponse(base64_decode($challenge->innerXml))));

        $this->_client->write($response);
    }

    private function handleAuthResult(XmlElement $result)
    {
        // todo: handle different scenarios
        if ($result->localName === 'failure') {
            throw new AuthenticationException('Unable to auth. '.trim($result->innerXml));
        }

        $this->_client->getLogger()->info('Successfully authorized as {name}.', ['name' => (string)$this->_client->jid]);
        $this->_client->restart();
    }

    public function setPassword(string $password = null)
    {
        $this->_password = $password;
    }

    private function _resultPredicate()
    {
        return all(with\element\name(in('success', 'failure')), with\element\xmlns(self::XMLNS));
    }
}
