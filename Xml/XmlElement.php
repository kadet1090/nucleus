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


class XmlElement extends \DOMElement
{
    public function __toString()
    {
        if($this->ownerDocument) {
            return $this->ownerDocument->saveXML($this);
        } else {
            $clone = $this->cloneNode(true);
            $clone = self::_document()->importNode($clone, true);
            
            return self::_document()->saveXML($clone);
        }
    }

    /** @return static */
    public static function create($name = 'element', $content = null, $uri = null)
    {
        return self::_document()->importNode(new static($name, $content, $uri));
    }

    private static function _document()
    {
        static $document;
        if(!isset($document)) {
            $document = new XmlDocument();
        }

        return $document;
    }

    /** @return XmlElement[] */
    public function elements($name, $uri = null) : array
    {
        $nodes = $uri === null ? $this->getElementsByTagName($name) : $this->getElementsByTagNameNS($uri, $name);

        return iterator_to_array($nodes);
    }
}
