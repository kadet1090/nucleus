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

namespace Kadet\Xmpp\Stream\Features;

use Kadet\Xmpp\Xml\XmlElement;

/**
 * Class StartTls
 * @package Kadet\Xmpp\Stream\Features
 *
 * @property-read bool       $required   True if encryption is required by server.
 * @property-read XmlElement $mechanisms All available mechanisms
 */
class StartTls extends XmlElement
{
    const XMLNS = 'urn:ietf:params:xml:ns:xmpp-tls';

    /**
     * StartTls constructor.
     */
    public function __construct()
    {
        parent::__construct('starttls', self::XMLNS);
    }

    public function getRequired()
    {
        return $this->get(\Kadet\Xmpp\Utils\filter\tag('required')) !== false;
    }
}
