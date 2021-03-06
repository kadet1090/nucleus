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
use Kadet\Xmpp\Xml\XmlFactoryCollocations;

/**
 * Class Stanza
 * @package Kadet\Xmpp\Stanza
 *
 * @property Jid|null $from  Jid representing "from" stanza attribute
 * @property Jid|null $to    Jid representing "to" stanza attribute
 * @property string   $type  Stanza type
 * @property string   $id    Unique stanza id
 * @property Error    $error Error details
 */
class Stanza extends XmlElement implements XmlFactoryCollocations
{
    /** @var bool|Jid */
    private $_from = false;
    /** @var bool|Jid */
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
     */
    public function __construct(string $kind, array $options = [])
    {
        $this->regenerateId($kind);
        parent::__construct($kind, 'jabber:client', $options);
    }

    public function getFrom()
    {
        if((string)$this->_from !== $this->hasAttribute('from')) {
            $this->_from = $this->hasAttribute('from') ? new Jid($this->getAttribute('from')) : null;
        }

        return $this->_from;
    }

    public function getTo()
    {
        if((string)$this->_to !== $this->hasAttribute('to')) {
            $this->_to = $this->hasAttribute('to') ? new Jid($this->getAttribute('to')) : null;
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

    public function getError()
    {
        return $this->element('error');
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
        $response = static::plain($this->fullName, $this->namespace);

        $response->to   = $this->from;
        $response->from = $this->to;
        $response->id   = $this->id;

        return $response;
    }

    public static function getXmlCollocations() : array
    {
        return [
            [ Error::class, 'name' => 'error', 'uri' => 'jabber:client' ],
        ];
    }
}
