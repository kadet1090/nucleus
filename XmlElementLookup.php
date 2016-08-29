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

return [
    [\Kadet\Xmpp\Stream\Stream::class, 'name' => 'stream', 'uri' => \Kadet\Xmpp\Xml\XmlStream::NAMESPACE_URI],
    [\Kadet\Xmpp\Stream\Features::class, 'name' => 'features', 'uri' => \Kadet\Xmpp\XmppClient::NAMESPACE_URI],
    [\Kadet\Xmpp\Stream\Features\StartTls::class, 'name' => 'starttls', 'uri' => \Kadet\Xmpp\Stream\Features\StartTls::XMLNS],

    [\Kadet\Xmpp\Stream\Error::class, 'name' => 'error', 'uri' => \Kadet\Xmpp\XmppClient::NAMESPACE_URI],

    [\Kadet\Xmpp\Stanza\Stanza::class, 'uri' => 'jabber:client'],
    [\Kadet\Xmpp\Stanza\Iq::class, 'name' => 'iq', 'uri' => 'jabber:client'],

    [\Kadet\Xmpp\Stanza\Iq\Query\Roster::class, 'name' => 'query', 'uri' => 'jabber:iq:roster'],
];
