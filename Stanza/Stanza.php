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

namespace Kadet\Xmpp\Stanza;


use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Xml\XmlElement;

/**
 * Class Stanza
 * @package Kadet\Xmpp\Stanza
 *
 * @property Jid|null $from Jid representing "from" stanza attribute
 * @property Jid|null $to   Jid representing "to" stanza attribute
 * @property string   $type Stanza type
 * @property string   $id   Unique stanza id
 */
class Stanza extends XmlElement
{
    private $_from = false;
    private $_to   = false;

    /**
     * Stanza constructor.
     * @param string   $kind    Stanza kind. According to XMPP RFC, one of "iq", "message", "presence"
     * @param array    $options {
     *     @var Jid    $from    Jid representing "from" stanza attribute
     *     @var Jid    $to      Jid representing "to" stanza attribute
     *     @var string $id      Unique id, will be generated if omitted
     *     @var string $type    Stanza type
     * }
     * @param mixed    $content Content to append
     */
    public function __construct(string $kind, array $options = [], $content = null)
    {
        parent::__construct($kind, 'jabber:client', $content);

        $this->regenerateId($this->localName);
        $this->applyOptions($options);
    }

    public function getFrom()
    {
        if($this->_from === false) {
            $this->_from = $this->hasAttribute('from') ? new Jid($this->_from) : null;
        }

        return $this->_from;
    }

    public function getTo()
    {
        if($this->_to === false) {
            $this->_to = $this->hasAttribute('to') ? new Jid($this->_to) : null;
        }

        return $this->_to;
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getId()
    {
        return $this->getAttribute('id');
    }

    public function setFrom($from)
    {
        if($from instanceof Jid) {
            $this->_from = $from instanceof Jid ? $from : new Jid($from);
        }

        $this->setAttribute('from', (string)$from);
    }

    public function setTo($to)
    {
        if($to instanceof Jid) {
            $this->_to = $to instanceof Jid ? $to : new Jid($to);
        }

        $this->setAttribute('to', (string)$to);
    }

    public function setType(string $type)
    {
        $this->setAttribute('type', $type);
    }

    public function setId(string $id)
    {
        $this->setAttribute('id', $id);
    }

    public function regenerateId(string $prefix = null)
    {
        $this->id = uniqid($prefix, true);
    }

    public function response()
    {
        $response = static::plain($this->name, $this->namespace);

        $response->to   = $this->from;
        $response->from = $this->to;
        $response->id   = $this->id;

        return $response;
    }

    /**
     * Initializes element with given name and URI
     *
     * @param string $name Element name, including prefix if needed
     * @param string $uri  Namespace URI of element
     */
    protected function init(string $name, string $uri = null)
    {
        parent::init($name, $uri);
        $this->regenerateId();
    }


}
