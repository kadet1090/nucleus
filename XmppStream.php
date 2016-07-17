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


use Kadet\Xmpp\Network\SecureStream;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlElementFactory;
use Kadet\Xmpp\Xml\XmlStream;
use React\Stream\DuplexStreamInterface;

class XmppStream extends XmlStream
{
    private $_attributes = [];

    public function __construct(XmlElementFactory $factory, DuplexStreamInterface $stream)
    {
        parent::__construct($factory, $stream);

        $this->factory->register(Features::class, self::NAMESPACE_URI, 'features');

        $this->on('element', function (XmlElement $element) {
            if($element instanceof Features) {
                $this->handleFeatures($element);
            } elseif(
                $element->namespaceURI === 'urn:ietf:params:xml:ns:xmpp-tls' &&
                $element->localName === 'proceed'
            ) {
                $this->readable->encrypt(STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->restart();
            }
        });
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

    private function processElement(XmlElement $element)
    {
        
    }

    private function handleFeatures(Features $element)
    {
        if($element->startTls >= Features::TLS_AVAILABLE) {
            if($this->readable instanceof SecureStream && $this->writable instanceof SecureStream) {
                $this->write(XmlElement::create('starttls', null, 'urn:ietf:params:xml:ns:xmpp-tls'));
            } elseif($element->startTls === Features::TLS_REQUIRED) {
                throw new \LogicException('Encryption is not available, but server requires it.');
            } else {
                // todo: warning
            }
        }
    }
}
