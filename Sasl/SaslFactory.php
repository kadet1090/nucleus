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

namespace Kadet\Xmpp\Sasl;


use Fabiang\Sasl\Sasl;

class SaslFactory extends Sasl
{
    protected $mechanisms = array(
        'anonymous' => 'Fabiang\\Sasl\\Authentication\\Anonymous',
        'login'     => 'Fabiang\\Sasl\\Authentication\\Login',
        'plain'     => 'Fabiang\\Sasl\\Authentication\\Plain',
        'external'  => 'Fabiang\\Sasl\\Authentication\\External',
        'crammd5'   => 'Fabiang\\Sasl\\Authentication\\CramMD5',
        'digestmd5' => 'Kadet\\Xmpp\\Sasl\\DigestMD5',
    );
}