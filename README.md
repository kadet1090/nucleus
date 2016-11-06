![Nucleus Logo](https://dl.dropboxusercontent.com/u/60020102/ShareX/2016-07/nucleus_Logo%20%2B%20Logotyp%20-%20Color.png)
# [WiP] Nucleus - XMPP Library for PHP
[![Packagist](https://img.shields.io/packagist/v/kadet/nucleus.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/kadet/nucleus)
![Milestone](https://img.shields.io/badge/milestone-2-yellow.svg)
[![Travis](https://img.shields.io/travis/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)](https://travis-ci.org/kadet1090/nucleus)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)](https://scrutinizer-ci.com/g/kadet1090/nucleus/?branch=master)
[![Code Climate](https://img.shields.io/codeclimate/github/kadet1090/nucleus.svg?maxAge=2592000)]()
![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)

Asynchronous XMPP library for PHP based on [React PHP](https://github.com/reactphp). Library is still work in progress,
so I don't recommend using it. It obsoletes my old [`kadet/xmpp`](https://github.com/kadet1090/xmpp) package.

## Already available
### Modular Client class
By design client class (`\Kadet\Xmpp\XmppClient`) acts like stream - for sending and receiving packets over network, 
event emitter to inform about events, and dependency container (what a wonderful violation of SRP) for managing modules.
It allows to move almost all logic outside of that class into proper and exchangeable components.

Basic client instance can be set up quite easily:
```php
$loop   = React\EventLoop\Factory::create();
$client = new \Kadet\Xmpp\XmppClient(new \Kadet\Xmpp\Jid('local@domain.tld/resource'), [
    'loop'     => $loop,
    'password' => 'epicpasspeoem',
]);

// Event declatation ...

$client->connect();
$loop->run();
```

Options passed to second argument, are equivalent to C#'s property instantiation, so above example is same as calling:
```php
$client = new \Kadet\Xmpp\XmppClient(new \Kadet\Xmpp\Jid('local@domain.tld/resource'));
$client->loop = $loop;
$client->password = 'epicpasspoem';
```

With exception for `modules` and `default-modules` which are used for initial module setup. You can disable default
modules by setting `default-modules` to false, but it's highly not recommended for non-test purposes.

Available events are:
```php
element(Kadet\Xmpp\Xml\XmlElement $element) // element received
features(Kadet\Xmpp\StreamFeatures $features) // features received

send.element(Kadet\Xmpp\Xml\XmlElement $element) // element sent
send.text(string $data) // some text (non valid XmlElement) sent

stream.open(Kadet\Xmpp\Xml\XmlElement $stream) // Stream started
stream.close() // Stream closed

stream.error(Kadet\Xmpp\Stream\Error $error) // Stream errored

connect(StreamDuplexInterface $stream) // called when connection is ready
exception(Exception $exception) // called when otherwise unhandled exception happens
```
also, all default events from [`react/stream`] are applicable.

#### TLS Handling
Most of XMPP servers require TLS connection, by default React streams don't support encryption. Library will handle 
encryption if underlying stream implements `\Kadet\Xmpp\Network\SecureStream` interface (provided stream classes like 
`\Kadet\Xmpp\Network\TcpStream` implements it by default). 

### (Better)Event API
Nucleus uses extended version of [`evenement/evenement`] to provide convenient `EventEmitter` API. So you can now filter
events by predicates and event queue is prioritized. 

```php
$emitter->on($event, $callback, $predicate = null, $priority = 0);
```

Predicate, as well as callback is called with arguments passed to event. There are few default predicates that you can
use, they can be found in [`Utils/Filter.php`](Utils/Filter.php).

```php
// Will fire event only if element belongs into self::TLS_NAMESPACE.
$stream->on('element', $callable, with\xmlns(self::TLS_NAMESPACE));
```

Also you can prioritize events
```php
$stream->on('element', $second, null, 0);
$stream->on('element', $first, null, 1); // will fire first
```

Sender argument is not provided by default, if needed you have to partially apply function, there is also shortcut in
every event emitting class.
```php
$stream->on('element', $stream->reference($callable)); // Will fire $callable($stream, ...$arguments);
```

Event queue can be stopped by returning `false` by event.

## Things to do
See roadmap on [Trello], I'll keep it updated. Project is created in milestone system, it means that after completing
each milestone API should be stable - but it's not guaranteed at the moment.


[Trello]:  https://trello.com/b/WHQ6d3hw/xmpp
[rfc6120]: xmpp.org/rfcs/rfc6120.html
[`evenement/evenement`]: https://packagist.org/packages/evenement/evenement
[`react/stream`]: https://github.com/reactphp/stream
