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

namespace Kadet\Xmpp\Module;

use Fabiang\Sasl\Authentication\AuthenticationInterface;
use Fabiang\Sasl\Authentication\ChallengeAuthenticationInterface;
use Fabiang\Sasl\Exception\InvalidArgumentException;
use Fabiang\Sasl\Sasl;
use Kadet\Xmpp\Exception\Protocol\AuthenticationException;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

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
     * Authentication constructor.
     *
     * @param string $password Client's password
     */
    public function __construct($password)
    {
        $this->_password = $password;
    }

    public function setClient(XmppClient $client)
    {
        parent::setClient($client);

        $client->on('features', function (Features $features) {
            return $this->handleFeatures($features);
        });
    }

    protected function handleFeatures(Features $features)
    {
        if (!empty($features->mechanisms)) {
            $sasl = new Sasl();
            foreach ($features->mechanisms as $name) {
                try {
                    $mechanism = $sasl->factory($name, [
                        'authcid'  => $this->_client->jid->local,
                        'secret'   => $this->_password,
                        'hostname' => $this->_client->jid->domain,
                        'service'  => 'xmpp'
                    ]);

                    $this->_client->getLogger()->debug('Starting auth using {name} mechanism.', ['name' => $name]);

                    $auth = new XmlElement('auth', self::XMLNS);
                    $auth->setAttribute('mechanism', $name);
                    if ($mechanism instanceof ChallengeAuthenticationInterface) {
                        try {
                            $response = base64_encode($mechanism->createResponse());
                        } catch (InvalidArgumentException $e) {
                            $response = '=';
                        }

                        $auth->append($response);

                        $callback = $this->_client->on('element', function (XmlElement $challenge) use ($mechanism) {
                            $this->handleChallenge($challenge, $mechanism);
                        }, with\all(with\tag('challenge'), with\xmlns(self::XMLNS)));

                        $this->_client->once('element', function (XmlElement $result) use ($callback) {
                            $this->_client->removeListener('element', $callback);
                            $this->handleAuthResult($result, $callback);
                        }, with\all(with\any(with\tag('success'), with\tag('failure')), with\xmlns(self::XMLNS)));
                    } else {
                        $auth->append(base64_encode($mechanism->createResponse()));
                    }
                    $this->_client->write($auth);

                    return false;
                } catch (InvalidArgumentException $e) {
                }
            }
        }

        return true;
    }

    private function handleChallenge(XmlElement $challenge, AuthenticationInterface $mechanism)
    {
        $response = new XmlElement('response', self::XMLNS);
        $response->append(base64_encode($mechanism->createResponse(base64_decode($challenge->innerXml))));

        $this->_client->write($response);
    }

    private function handleAuthResult(XmlElement $result, callable $callback)
    {
        if ($result->localName === 'failure') {
            throw new AuthenticationException('Unable to auth. '.trim($result->innerXml));
        }

        $this->_client->getLogger()->info('Successfully authorized as {name}.', ['name' => (string)$this->_client->jid]);
        $this->_client->restart();
    }

    public function setPassword(string $password)
    {
        $this->_password = $password;
    }

    public function auth()
    {
        $this->handleFeatures($this->_client->features);
    }
}
