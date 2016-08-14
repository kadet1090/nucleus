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

use Traversable;

class DnsResolver implements \IteratorAggregate
{
    private $_results = null;
    private $_pool;

    public function __construct(array $pool)
    {
        $this->_pool = $pool;
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *        <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        $this->resolve();

        return new \ArrayIterator($this->_results);
    }

    public function resolve(bool $force = false) : bool
    {
        if (!$force && is_array($this->_results)) {
            return true;
        }

        $this->_results = [];
        foreach ($this->_pool as $address => $type) {
            if (!($result = dns_get_record($address, $type))) {
                continue;
            }

            $this->_results = array_merge($this->_results, array_map(function ($record) {
                return [$record['target'], $record['port']];
            }, $result));
        }

        return true;
    }
}
