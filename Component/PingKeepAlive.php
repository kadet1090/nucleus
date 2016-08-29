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


use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\XmppClient;

class PingKeepAlive extends Component
{
    /** @var float|int */
    private $_interval = 15;
    private $_timer;

    /**
     * PingKeepAlive constructor.
     *
     * @param float $interval Keep alive interval in seconds
     */
    public function __construct($interval = 15.)
    {
        $this->_interval = $interval;
    }

    public function setClient(XmppClient $client)
    {
        parent::setClient($client);

        $this->_client->on('state', [$this, 'enable'], \Kadet\Xmpp\Utils\filter\equals('ready'));
    }

    /**
     * Starts keep alive timer
     */
    public function enable()
    {
        $this->_timer = $this->_client->connector->getLoop()->addPeriodicTimer($this->_interval, function() {
            $this->keepAlive();
        });
    }

    /**
     * Stops keep alive timer
     */
    public function disable()
    {
        $this->_client->connector->getLoop()->cancelTimer($this->_timer);
    }

    private function keepAlive()
    {
        $ping = new Iq(['type' => 'get', 'query' => new Iq\Query('urn:xmpp:ping', 'ping')]);

        $this->_client->write($ping);
    }
}
