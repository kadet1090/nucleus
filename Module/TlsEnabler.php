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

use Kadet\Xmpp\Exception\Protocol\TlsException;
use Kadet\Xmpp\Network\SecureStream;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;

class TlsEnabler extends ClientModule
{
    public function setClient(XmppClient $client)
    {
        parent::setClient($client);

        $client->on('features', function (Features $features) {
            return !$this->startTls($features);
        }, null, 10);

        $client->on('element', function (XmlElement $element) {
            $this->handleTls($element);
        }, with\element\xmlns(Features\StartTls::XMLNS));
    }

    public function startTls(Features $features)
    {
        if ($features->startTls) {
            if ($this->_client->getDecorated() instanceof SecureStream) {
                $this->_client->write(new Features\StartTls());

                return true; // Stop processing
            } elseif ($features->startTls->required) {
                throw new TlsException('Encryption is not available, but server requires it.');
            } else {
                $this->_client->getLogger()->warning('Server offers TLS encryption, but stream is not capable of it.');
            }
        }

        return false;
    }

    private function handleTls(XmlElement $response)
    {
        if ($response->localName === 'proceed') {
            // this function is called only by event, which can be only fired after instanceof check
            /** @noinspection PhpUndefinedMethodInspection */
            $this->_client->getDecorated()->encrypt(STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->_client->restart();
        } else {
            throw new TlsException('TLS negotiation failed.'); // XMPP does not provide any useful information why it happened
        }
    }
}
