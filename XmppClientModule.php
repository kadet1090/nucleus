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

namespace Kadet\Xmpp;

use Kadet\Xmpp\Module\ClientModuleInterface;

abstract class XmppClientModule implements ClientModuleInterface
{
    /**
     * Reference to XMPP Client instance.
     *
     * @var XmppClient
     */
    protected $_client;

    public function setClient(XmppClient $client)
    {
        $this->_client = $client;
    }
}
