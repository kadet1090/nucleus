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
use Kadet\Xmpp\XmppStream;

/**
 * Class Features
 * @package Kadet\Xmpp\Stream
 *
 * @property-read int $startTls
 */
class Features extends XmlElement
{
    const TLS_UNAVAILABLE = false;
    const TLS_AVAILABLE   = true;
    const TLS_REQUIRED    = 2;

    use Accessors;

    public function getStartTls()
    {
        if (!($tls = $this->element('starttls', XmppStream::TLS_NAMESPACE))) {
            return self::TLS_UNAVAILABLE;
        }

        if ($tls->element('required')) {
            return self::TLS_REQUIRED;
        }

        return self::TLS_AVAILABLE;
    }
}