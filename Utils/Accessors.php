<?php

namespace Kadet\Xmpp\Utils;

trait Accessors
{
    private $_magic = [];

    public function __get($property)
    {
        $getter = 'get' . ucfirst($property);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . ucfirst($property))) {
            // todo: Exception, write-only
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
            // todo: Exception, read-only
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
}
