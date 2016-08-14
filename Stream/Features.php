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

use Kadet\Xmpp\Stream\Features\StartTls;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlStream;

/**
 * Class Features
 * @package Kadet\Xmpp\Stream
 *
 * @property-read false|StartTls $startTls
 * @property-read string[]       $mechanisms
 */
class Features extends XmlElement
{
    use Accessors;

    /**
     * XmlElement constructor
     *
     * @param array $features
     */
    public function __construct(array $features = [])
    {
        parent::__construct('stream:features', XmlStream::NAMESPACE_URI, $features);
    }

    /**
     * @return false|StartTls
     */
    public function getStartTls()
    {
        return $this->get(StartTls::class);
    }

    public function getMechanisms()
    {
        return array_map(function (XmlElement $element) {
            return $element->innerXml;
        }, $this->get(\Kadet\Xmpp\Utils\filter\tag('mechanisms'))->children ?? []);
    }
}
