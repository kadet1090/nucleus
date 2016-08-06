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

namespace Kadet\Xmpp\Stream;

use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Xml\XmlElement;

/**
 * Class Error
 * @package Kadet\Xmpp\Stream
 *
 * @property-read string $kind Stream error defined condition
 * @property-read string $text Stream error text description
 *
 * @see http://xmpp.org/rfcs/rfc6120.html#streams-error-syntax
 */
class Error extends XmlElement
{
    use Accessors;

    public function getKind()
    {
        return $this->query("./xmpp:*")->with('xmpp', 'urn:ietf:params:xml:ns:xmpp-streams')->query()->current()->localName;
    }

    public function getText()
    {
        if ($text = $this->query(".//xmpp:text")->with('xmpp', 'urn:ietf:params:xml:ns:xmpp-streams')->query()->current()) {
            return $text;
        }

        return null;
    }
}
