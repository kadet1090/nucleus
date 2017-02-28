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
use \Kadet\Xmpp\Utils\filter as with;
use React\EventLoop\Timer\TimerInterface;

class PingKeepAlive extends Component
{
    /** @var float|int */
    private $_interval = 15;
    /** @var TimerInterface */
    private $_timer;

    private $_handler = null;

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

        $this->_handler = $this->_client->on('iq', function(Iq $iq) {
            $this->handleIq($iq);
        }, with\all(with\iq\query(with\element('ping', 'urn:xmpp:ping')), with\stanza\type('get')));
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
        $this->_timer->cancel();
        $this->_client->removeListener('iq', $this->_handler);
    }

    private function keepAlive()
    {
        $ping = new Iq('get', ['query' => new Iq\Query('urn:xmpp:ping', 'ping')]);

        $this->_client->write($ping);
    }

    private function handleIq(Iq $iq)
    {
        $response = $iq->response();
        $response->type  = 'result';
        $response->query = new Iq\Query('urn:xmpp:ping', 'ping');

        $this->_client->write($response);
    }
}
