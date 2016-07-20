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

class Error extends XmlElement
{
    use Accessors;

    public function getKind()
    {
        return $this->query("./xmpp:*")->with('xmpp', 'urn:ietf:params:xml:ns:xmpp-streams')->query()->item(0)->localName;
    }

    public function getText()
    {
        return $this->query("./xmpp:text")->with('xmpp', 'urn:ietf:params:xml:ns:xmpp-streams')->query()->item(0)->localName;
    }
}
