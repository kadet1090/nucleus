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

namespace Kadet\Xmpp\Stream;


use Kadet\Xmpp\Xml\XmlElement;

class Stream extends XmlElement
{
    /**
     * XmlElement constructor
     *
     * @param array  $options    {
     * @var mixed    $content    Content of element
     * @var array    $attributes Element attributes
     *                           }
     */
    public function __construct(array $options)
    {
        parent::__construct('stream:stream', 'http://etherx.jabber.org/streams', $options);
    }


    public function setInnerXml($value)
    {
        return false;
    }

    public function setContent($value)
    {
        return false;
    }

    /**
     * Appends child to element
     *
     * @param XmlElement|string $element
     *
     * @return XmlElement|string Same as $element
     */
    public function appendChild($element)
    {
        if ($element instanceof XmlElement) {
            $element->parent = $this;
        }
    }

}
