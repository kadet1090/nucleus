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

use Kadet\Xmpp\Exception\ReadOnlyException;
use Kadet\Xmpp\Exception\WriteOnlyException;

trait Accessors
{
    private $_magic = [];

    public function __get($property)
    {
        $getter = 'get' . ucfirst($property);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . ucfirst($property))) {
            throw new WriteOnlyException("Property \$$property is write-only, which is rather strange. Maybe you should write custom getter with proper explanation?");
        } else {
            return $this->_get($property);
        }
    }

    public function __set($property, $value)
    {
        $setter = 'set' . ucfirst($property);

        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . ucfirst($property))) {
            throw new ReadOnlyException("Property \$$property is read-only.");
        } else {
            $this->_magic[$property] = $value;
        }
    }

    public function __isset($property)
    {
        return $this->$property !== null;
    }

    public function __unset($property)
    {
        $this->$property = null;
    }

    public function _get($property)
    {
        return isset($this->_magic[$property]) ? $this->_magic[$property] : null;
    }

    public function _set($property, $value)
    {
        $this->_magic[$property] = $value;
    }

    public function applyOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }
}
