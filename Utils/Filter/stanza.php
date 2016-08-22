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
namespace Kadet\Xmpp\Utils\filter\stanza;

use Kadet\Xmpp\Utils\filter\element;

function id($id) {
    return element\attribute('id', $id);
}

function type($type) {
    return element\attribute('type', $type);
}
