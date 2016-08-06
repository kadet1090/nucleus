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

namespace Kadet\Xmpp\Utils;

use Evenement\EventEmitterInterface;

interface BetterEmitterInterface extends EventEmitterInterface
{
    public function on($event, callable $listener, $condition = null, int $priority = 0);
    public function once($event, callable $listener, $condition = null, int $priority = 0);
}
