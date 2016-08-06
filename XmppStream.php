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
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlParser;
use Kadet\Xmpp\Xml\XmlStream;
use React\Stream\DuplexStreamInterface;

class XmppStream extends XmlStream
{

    private $_attributes = [];
    private $_lang;

    public function __construct(XmlParser $parser, DuplexStreamInterface $transport = null, string $lang = 'en')
    {
        parent::__construct($parser, $transport);

        $this->_parser->factory->load(require __DIR__.'/XmlElementLookup.php');

        $this->_lang = $lang;

        $this->on('element', function (Features $element) {
            $this->handleFeatures($element);
        }, Features::class);

        $this->on('element', function (XmlElement $element) {
            $this->handleTls($element);
        }, with\xmlns(Features\StartTls::XMLNS));
    }

    public function start(array $attributes = [])
    {
        $this->_attributes = $attributes;

        parent::start(array_merge([
            'xmlns'    => 'jabber:client',
            'version'  => '1.0',
            'xml:lang' => $this->_lang
        ], $attributes));
    }

    public function restart()
    {
        $this->getLogger()->debug('Restarting stream', $this->_attributes);
        $this->start($this->_attributes);
    }

    protected function handleFeatures(Features $features)
    {
        if ($features->startTls) {
            if ($this->_decorated instanceof SecureStream) {
                $this->write(new Features\StartTls());

                return true; // Stop processing
            } elseif ($features->startTls->required) {
                throw new TlsException('Encryption is not available, but server requires it.');
            } else {
                $this->getLogger()->warning('Server offers TLS encryption, but stream is not capable of it.');
            }
        }

        return null;
    }

    private function handleTls(XmlElement $response)
    {
        if ($response->localName === 'proceed') {
            // this function is called only by event, which can be only fired after instanceof check
            /** @noinspection PhpUndefinedMethodInspection */
            $this->_decorated->encrypt(STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->restart();
        } else {
            throw new TlsException('TLS negotiation failed.'); // XMPP does not provide any useful information why it happened
        }
    }
}
