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

/**
 * Class describing root element of stream.
 * It doesn't store any references to it's children, only children can refer that class, so they can be garbage
 * collected when needed. It's used mainly for storing default namespace for stanzas.
 *
 * @package Kadet\Xmpp\Stream
 *
 * @property string $xmlns    Default namespace for stanzas
 * @property string $language Stream language
 * @property string $version  Stream version
 * @property string $to       Server vhost
 *
 * @internal
 */
class Stream extends XmlElement
{
    /**
     * Stream constructor
     *
     * @param array  $options {
     *     @var mixed    $content    Content of element
     *     @var string   $xmlns      Namespace of stream elements
     *     @var array    $attributes Stream attributes
     * }
     */
    public function __construct(array $options)
    {
        parent::__construct('stream:stream', 'http://etherx.jabber.org/streams', $options);
    }

    //region Default Namespace
    public function setXmlns($uri)
    {
        $this->setNamespace($uri, null);
    }

    public function getXmlns()
    {
        return $this->getNamespace(null);
    }
    //endregion

    public function setInnerXml($value)
    {
        return false;
    }

    public function setContent($value)
    {
        return false;
    }

    public function appendChild($element)
    {
        if ($element instanceof XmlElement) {
            $element->parent = $this;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->getAttribute('xml:language');
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language)
    {
        $this->setAttribute('xml:language', $language);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getAttribute('version');
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version)
    {
        $this->setAttribute('version', $version);
    }

    #region To
    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->getAttribute('to');
    }

    /**
     * @param string $to
     */
    public function setTo(string $to)
    {
        $this->setAttribute('to', $to);
    }
    #endregion
}
