<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2017, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Stanza\Message;


use Kadet\Xmpp\Xml\XmlElement;

class Body extends XmlElement
{

    /**
     * Body constructor.
     * @param null  $language
     * @param array $options
     */
    public function __construct($language = null, $options = [])
    {
        parent::__construct('body', null, ['language' => $language]);
    }

    public function setLanguage(string $language = null)
    {
        $this->setAttribute('lang', $language, XmlElement::XML);
    }

    public function getLanguage()
    {
        return $this->getAttribute('lang', XmlElement::XML);
    }

    public function __toString()
    {
        return $this->innerXml;
    }
}