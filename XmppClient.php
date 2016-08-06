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

namespace Kadet\Xmpp;


use Fabiang\Sasl\Authentication\AuthenticationInterface;
use Fabiang\Sasl\Authentication\ChallengeAuthenticationInterface;
use Fabiang\Sasl\Sasl;
use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Exception\Protocol\AuthenticationException;
use Kadet\Xmpp\Network\Connector;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlElementFactory;
use Kadet\Xmpp\Xml\XmlParser;
use React\EventLoop\LoopInterface;

use Kadet\Xmpp\Utils\filter as with;

/**
 * Class XmppClient
 * @package Kadet\Xmpp
 *
 * @property-read Jid $jid Client's jid (Jabber Identifier) address.
 */
class XmppClient extends XmppStream
{
    const SASL_NAMESPACE = 'urn:ietf:params:xml:ns:xmpp-sasl';

    /**
     * Connector used to instantiate stream connection to server.
     *
     * @var Connector
     */
    protected $_connector;

    /**
     * Client's jid (Jabber Identifier) address.
     *
     * @var Jid
     */
    protected $_jid;

    /**
     * Client's password used in authorisation.
     *
     * @var string
     */
    protected $_password;


    /**
     * XmppClient constructor.
     * @param Jid                     $jid
     * @param string                  $password
     * @param Connector|LoopInterface $connector
     * @param XmlParser|null          $parser
     * @param string                  $lang
     */
    public function __construct(Jid $jid, string $password, $connector = null, XmlParser $parser = null, $lang = 'en')
    {
        parent::__construct(
            $parser ?: new XmlParser(new XmlElementFactory()),
            null, // will be set by event
            $lang
        );

        $this->_jid      = $jid;
        $this->_password = $password;

        $this->setConnector($connector);
        $this->connect();

        $this->_connector->on('connect', function(...$arguments) {
            return $this->emit('connect', $arguments);
        });
    }

    public function connect()
    {
        $this->getLogger()->debug("Connecting to {$this->_jid->domain}");

        $this->_connector->connect();
    }

    public function getJid()
    {
        return $this->_jid;
    }

    private function handleConnect($stream)
    {
        $this->exchangeStream($stream);

        $this->getLogger()->info("Connected to {$this->_jid->domain}");
        $this->start([
            'from' => (string)$this->_jid,
            'to'   => $this->_jid->domain
        ]);
    }

    protected function handleFeatures(Features $features)
    {
        if(parent::handleFeatures($features)) {
            return true;
        }

        if(!empty($features->mechanisms)) {
            $sasl = new Sasl();
            foreach($features->mechanisms as $name)
            {
                try {
                    $mechanism = $sasl->factory($name, [
                        'authcid'  => $this->_jid->local,
                        'secret'   => $this->_password,
                        'hostname' => $this->_jid->domain,
                        'service'  => 'xmpp'
                    ]);

                    $this->getLogger()->debug('Starting auth using {name} mechanism.', ['name' => $name]);

                    $auth = new XmlElement('auth', self::SASL_NAMESPACE);
                    $auth->setAttribute('mechanism', $name);
                    if($mechanism instanceof ChallengeAuthenticationInterface) {
                        try {
                            $response = base64_encode($mechanism->createResponse());
                        } catch (\Fabiang\Sasl\Exception\InvalidArgumentException $e) {
                            $response = '=';
                        }

                        $auth->append($response);

                        $callback = $this->on('element', function(XmlElement $challenge) use ($mechanism) {
                            $this->handleChallenge($challenge, $mechanism);
                        }, with\all(with\tag('challenge'), with\xmlns(self::SASL_NAMESPACE)));

                        $this->on('element', function(XmlElement $result) use ($callback) {
                            $this->handleAuthResult($result, $callback);
                        }, with\all(with\any(with\tag('success'), with\tag('failure')), with\xmlns(self::SASL_NAMESPACE)));
                    } else {
                        $auth->append(base64_encode($mechanism->createResponse()));
                    }
                    $this->write($auth);

                    return true;
                } catch (\Fabiang\Sasl\Exception\InvalidArgumentException $e) { }
            }

            throw new AuthenticationException('None of available mechanisms are supported.');
        }

        return null;
    }

    private function handleChallenge(XmlElement $challenge, AuthenticationInterface $mechanism)
    {
        $response = new XmlElement('response', self::SASL_NAMESPACE);
        $response->append(base64_encode($mechanism->createResponse(base64_decode($challenge->innerXml))));

        $this->write($response);
    }

    private function handleAuthResult(XmlElement $result, callable $callback)
    {
        $this->removeListener('element', $callback);

        if($result->localName === 'failure') {
            throw new AuthenticationException('Unable to auth.', [trim($result->innerXml)]);
        }

        $this->getLogger()->info('Successfully authorized as {name}.', ['name' => (string)$this->_jid]);
        $this->restart();
    }

    /**
     * @param $connector
     */
    protected function setConnector($connector)
    {
        if ($connector instanceof LoopInterface) {
            $this->_connector = new Connector\TcpXmppConnector($this->_jid->domain, $connector);
        } elseif ($connector instanceof Connector) {
            $this->_connector = $connector;
        } else {
            throw new InvalidArgumentException(sprintf(
                '$connector must be either %s, or %s instance %s given.',
                LoopInterface::class, Connector::class, \Kadet\Xmpp\Utils\helper\typeof($connector)
            ));
        }

        $this->_connector->on('connect', function($stream) {
            $this->handleConnect($stream);
        });
    }
}
