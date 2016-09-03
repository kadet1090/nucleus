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

namespace Kadet\Xmpp\Utils\filter\iq;

use function Kadet\Xmpp\Utils\filter\{
    instance, property
};

function query($predicate) {
    return property('query', $predicate instanceof \Closure ? $predicate : instance($predicate));
}
