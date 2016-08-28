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

namespace Kadet\Xmpp\Component;

use Kadet\Xmpp\XmppClient;

abstract class Component implements ComponentInterface
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
