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

namespace Kadet\Xmpp\Network;


use Kadet\Xmpp\Utils\BetterEmitterInterface;
use Kadet\Xmpp\Utils\LoggingInterface;
use React\EventLoop\LoopInterface;
use React\Stream\DuplexStreamInterface;

interface Connector extends LoggingInterface, BetterEmitterInterface
{
    public function __construct(string $host, LoopInterface $loop);
    public function connect(array $options = []) : DuplexStreamInterface;
    public function getLoop();
}
