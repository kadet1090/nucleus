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

use Traversable;

class PriorityCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array[]
     */
    private $_collection = [];
    private $_cache;


    /**
     * Retrieve an external iterator
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_cache);
    }

    /**
     * Count elements of an object
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->_cache);
    }

    public function insert($value, int $priority)
    {
        $this->_collection[] = [$priority, $value];

        $this->rebuildCache();
    }

    public function remove($value)
    {
        $this->_collection = array_filter($this->_collection, function ($e) use ($value) {
            return $e[1] !== $value;
        });

        $this->rebuildCache();
    }

    private function rebuildCache()
    {
        usort($this->_collection, function ($a, $b) {
            return $b[0] <=> $a[0];
        });

        $this->_cache = array_column($this->_collection, 1);
    }
}
