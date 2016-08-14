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

namespace Kadet\Xmpp\Utils;

use Interop\Container\ContainerInterface;

trait ServiceManager
{
    public function get($id)
    {
        return $this->getContainer()->get($id);
    }

    public function has($id)
    {
        return $this->getContainer()->has($id);
    }

    abstract protected function getContainer() : ContainerInterface;
}
