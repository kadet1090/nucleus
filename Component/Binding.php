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

namespace Kadet\Xmpp\Component;


use Kadet\Xmpp\Exception\Protocol\BindingException;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\XmppClient;
use \Kadet\Xmpp\Utils\filter as with;

class Binding extends Component
{
    const XMLNS = 'urn:ietf:params:xml:ns:xmpp-bind';

    public function setClient(XmppClient $client)
    {
        parent::setClient($client);

        $client->on('features', function (Features $features) {
            return !$this->bind($features);
        });
    }

    public function bind(Features $features)
    {
        if($features->has(with\element('bind', self::XMLNS))) {
            $stanza = new Iq(['type' => 'set']);
            $bind = $stanza->append(new Iq\Query(self::XMLNS, 'bind'));

            if(!$this->_client->jid->isBare()) {
                $bind->append(new XmlElement('resource', null, ['content' => $this->_client->jid->resource]));
            }

            $this->_client->once('element', function(Iq $element) {
                $this->handleResult($element);
            }, with\stanza\id($stanza->id));

            $this->_client->write($stanza);
            return true;
        }

        return false;
    }

    public function handleResult(Iq $stanza)
    {
        if($stanza->type === 'error') {
            throw BindingException::fromError($this->_client->jid, $stanza->error);
        }

        $this->_client->bind($stanza->element('bind', self::XMLNS)->element('jid')->innerXml);
    }
}
