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

namespace Kadet\Xmpp\Stanza\Iq;


use Kadet\Xmpp\Xml\XmlElement;

class Query extends XmlElement
{
    /**
     * Query constructor
     *
     * @param string $uri        Namespace URI of element
     * @param string $name       Element name, including prefix if needed
     * @param array  $options    {
     * @var mixed    $content    Content of element
     * @var array    $attributes Element attributes
     *                           }
     */
    public function __construct($uri, $name = 'query', array $options = [])
    {
        parent::__construct($name, $uri, $options);
    }

}
