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

use Kadet\Xmpp\Exception\Protocol\TlsException;
use Kadet\Xmpp\Network\SecureStream;
use Kadet\Xmpp\Stream\Error;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Utils\Logging;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlParser;
use Kadet\Xmpp\Xml\XmlStream;
use React\Stream\DuplexStreamInterface;
use Kadet\Xmpp\Utils\filter as with;

class XmppStream extends XmlStream
{
    const TLS_NAMESPACE = 'urn:ietf:params:xml:ns:xmpp-tls';

    use Logging;

    private $_attributes = [];

    public function __construct(XmlParser $parser, DuplexStreamInterface $transport)
    {
        parent::__construct($parser, $transport);

        $this->parser->factory->register(Features::class, self::NAMESPACE_URI, 'features');
        $this->parser->factory->register(Error::class, self::NAMESPACE_URI, 'error');

        $this->on('element', function (Features $element) { $this->handleFeatures($element); }, Features::class);
        $this->on('element', function (XmlElement $element) {
            $this->handleTls($element);
        }, with\xmlns(self::TLS_NAMESPACE));
    }

    public function start(array $attributes = [])
    {
        $this->_attributes = $attributes;

        parent::start(array_merge([
            'xmlns'   => 'jabber:client',
            'version' => '1.0'
        ], $attributes));
    }

    public function restart()
    {
        $this->start($this->_attributes);
    }

    private function handleFeatures(Features $element)
    {
        if ($element->startTls >= Features::TLS_AVAILABLE) {
            if ($this->readable instanceof SecureStream && $this->writable instanceof SecureStream) {
                $this->write(XmlElement::create('starttls', null, self::TLS_NAMESPACE));

                return; // Stop processing
            } elseif ($element->startTls === Features::TLS_REQUIRED) {
                throw new TlsException('Encryption is not available, but server requires it.');
            } else {
                $this->getLogger()->warning('Server offers TLS encryption, but stream is not capable of it.');
            }
        }
    }

    private function handleTls(XmlElement $response)
    {
        if ($response->localName === 'proceed') {
            // this function is called only by event, which can be only fired after instanceof check
            /** @noinspection PhpUndefinedMethodInspection */
            $this->readable->encrypt(STREAM_CRYPTO_METHOD_TLS_CLIENT);
            /** @noinspection PhpUndefinedMethodInspection */
            $this->writable->encrypt(STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->restart();
        } else {
            throw new TlsException('TLS negotiation failed.'); // XMPP does not provide any useful information why it happened
        }
    }
}
