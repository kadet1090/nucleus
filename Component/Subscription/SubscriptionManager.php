<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2017, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Component\Subscription;


use Kadet\Xmpp\Component\Component;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Presence;
use Kadet\Xmpp\Utils\BetterEmitter;

use function \Kadet\Xmpp\Utils\filter\{
    stanza\type, in
};

class SubscriptionManager extends Component
{
    use BetterEmitter;

    protected function init()
    {
        $this->_client->on('presence', function(...$args) {
            $this->handleSubscriptionRequest(...$args);
        }, type('subscribe'));
    }

    private function handleSubscriptionRequest(Presence $presence)
    {
        $this->emit('request', [ $presence ]);
    }

    /**
     * Sends subscription request presence to server.
     *
     * @param Jid|string $jid
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function subscribe($jid)
    {
        return $this->_client->send($this->presence('subscribe', $jid));
    }

    /**
     * Sends subscription removal request presence to server.
     *
     * @param Jid|string $jid
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function unsubscribe(Jid $jid)
    {
        return $this->_client->send($this->presence('unsubscribe', $jid));
    }

    /**
     * Sends subscription cancellation request presence to server.
     *
     * @param Jid|string $jid
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function cancel($jid)
    {
        return $this->_client->send($this->presence('unsubscribed', $jid));
    }

    private function presence($type, $jid)
    {
        $jid = $jid instanceof Jid ? $jid : new Jid($jid);
        return new Presence([
            'type' => $type,
            'to' => $jid->bare()
        ]);
    }
}