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

use DOMNode;

class XmlDocument extends \DOMDocument
{
    /**
     * @param DOMNode $importedNode
     * @param null $deep
     *
     * @return XmlElement
     */
    public function importNode(DOMNode $importedNode, $deep = null)
    {
        $this->registerNodeClass('DOMElement', get_class($importedNode));

        return parent::importNode($importedNode, $deep);
    }
}
