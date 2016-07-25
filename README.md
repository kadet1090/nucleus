![Nucleus Logo](https://dl.dropboxusercontent.com/u/60020102/ShareX/2016-07/nucleus_Logo%20%2B%20Logotyp%20-%20Color.png)
# [WiP] Nucleus - XMPP Library for PHP
[![Packagist](https://img.shields.io/packagist/v/kade1090/nucleus.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/kadet/nucleus)
![Milestone](https://img.shields.io/badge/milestone-1-yellow.svg)
[![Travis](https://img.shields.io/travis/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)](https://travis-ci.org/kadet1090/nucleus)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)](https://scrutinizer-ci.com/g/kadet1090/nucleus/?branch=master)
![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/kadet1090/nucleus.svg?maxAge=2592000?style=flat-square)

Asynchronous XMPP library for PHP based on [React PHP](https://github.com/reactphp). Library is still work in progress,
so I don't recommend using it. It obsoletes my old [`kadet/xmpp`](https://github.com/kadet1090/xmpp) package.

## Already available
### XMPP Stream handling
Stream handling is fully implemented, it can handle xml parsing, stream management (with errors), and writing data into 
connection. XMPP stream acts like proxy for underlying connection stream.

```php
$loop       = React\EventLoop\Factory::create();
$connection = new \Kadet\Xmpp\Network\TcpStream(stream_socket_client('tcp://server.xmpp:5222'), $loop); // subject to change

$factory = new \Kadet\Xmpp\Xml\XmlFactory(); // Factory is used for xml element creation
$parser  = new \Kadet\Xmpp\Xml\XmlParser($factory); // Parser converts received xml stream into `XmlElement`s

$stream = new \Kadet\Xmpp\XmppStream($parser, $connection); // Connection can be any object implementing DuplexStreamInterface from react

// declare events...

$stream->start([
    'from' => 'test@jid.pl',
    'to'   => 'jid.pl'
]);

$loop->run();
```

Available events are:
```php
element(Kadet\Xmpp\Xml\XmlElement $element) // element received

send.element(Kadet\Xmpp\Xml\XmlElement $element) // element sent
send.text(string $data) // some text (non valid XmlElement) sent

stream.open(Kadet\Xmpp\Xml\XmlElement $stream) // Stream started
stream.close() // Stream closed

stream.error(Kadet\Xmpp\Stream\Error $error) // Stream errored
```

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
