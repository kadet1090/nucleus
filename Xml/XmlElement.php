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

namespace Kadet\Xmpp\Xml;

/**
 * @property XmlDocument $ownerDocument
 */
class XmlElement extends \DOMElement
{
    /** @return static */
    public static function create($name = 'element', $content = null, $uri = null)
    {
        return self::_document()->importNode(new static($name, $content, $uri));
    }

    private static function _document() : XmlDocument
    {
        static $document;
        if (!isset($document)) {
            $document = new XmlDocument();
        }

        return $document;
    }

    public function __toString()
    {
        if ($this->ownerDocument) {
            return $this->ownerDocument->saveXML($this);
        } else {
            $clone = $this->cloneNode(true);
            $clone = self::_document()->importNode($clone, true);

            return self::_document()->saveXML($clone);
        }
    }

    /**
     * Retrieves array of matching elements
     *
     * @param string $name  Requested element tag name
     * @param null   $uri   Requested element namespace
     *
     * @return XmlElement[] Found Elements
     */
    public function elements($name, $uri = null) : array
    {
        $nodes = $uri === null ? $this->getElementsByTagName($name) : $this->getElementsByTagNameNS($uri, $name);

        return iterator_to_array($nodes); // todo: substitute with some iterator
    }

    /**
     * Returns one element at specified index (for default the first one).
     *
     * @param string $name  Requested element tag name
     * @param null   $uri   Requested element namespace
     * @param int    $index Index of element to retrieve
     *
     * @return XmlElement|false Retrieved element
     */
    public function element($name, $uri = null, $index = 0)
    {
        $nodes = $uri === null ? $this->getElementsByTagName($name) : $this->getElementsByTagNameNS($uri, $name);

        return $nodes->item($index < 0 ? $nodes->length + $index : $index) ?: false;
    }

    public function query($query)
    {
        return iterator_to_array($this->ownerDocument->xpath->query($query, $this)); // todo: substitute with some iterator
    }
}
