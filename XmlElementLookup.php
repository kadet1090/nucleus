<?php return [
    [\Kadet\Xmpp\Stream\Features::class, 'name' => 'features', 'uri' => \Kadet\Xmpp\XmppClient::NAMESPACE_URI],
    [\Kadet\Xmpp\Stream\Features\StartTls::class, 'name' => 'starttls', 'uri' => \Kadet\Xmpp\Stream\Features\StartTls::XMLNS],

    [\Kadet\Xmpp\Stream\Error::class, 'name' => 'error', 'uri' => \Kadet\Xmpp\XmppClient::NAMESPACE_URI],
];
