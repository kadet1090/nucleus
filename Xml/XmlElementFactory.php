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

namespace Kadet\Xmpp\Xml;

class XmlElementFactory
{
    /**
     * @var string[string]
     */
    private $lookup = [];
    
    public function lookup($namespace, $tag)
    {
        if (isset($this->lookup["$tag@$namespace"])) {
            return $this->lookup["$tag@$namespace"];
        } elseif (isset($this->lookup[$namespace])) {
            return $this->lookup[$namespace];
        } else {
            return XmlElement::class;
        }
    }

    public function register($class, $namespace, $tag = null)
    {
        if (is_array($namespace)) {
            $this->lookup = array_merge($this->lookup, $namespace);

            return;
        }

        if ($tag !== null) {
            $namespace = "$tag@$namespace";
        }

        $this->lookup[$namespace] = $class;
    }

    public function create($namespace, $tag, $arguments = [])
    {
        $class = $this->lookup($namespace, $tag);
        return new $class(...$arguments);
    }
}
