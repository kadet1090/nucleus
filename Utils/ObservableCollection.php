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


/**
 * Observable collection class.
 *
 * > means output
 *
 * ```php
 * $collection = new ObservableCollection();
 * $collection->on('set', function($value, $key) { echo "New item[$key]: $value".PHP_EOL; });
 * $collection->on('remove', function($value, $key) { echo "Item removed[$key]: $value".PHP_EOL; });
 * $collection->on('empty', function() { echo "list empty".PHP_EOL });
 *
 * $collection[] = "foo";
 * > New item[0]: foo
 * $collection[] = "bar"
 * > New item[1]: bar
 * unset($collection[1]);
 * > Item removed[1]: bar
 * $collection["dead"] = "beef"
 * > New item[dead]: deadbeef
 *
 * echo count($collection).PHP_EOL;
 * > 2
 *
 * unset($collection[0]);
 * > Item removed[0]: foo
 * unset($collection['dead']);
 * > Item removed[dead]: beed
 * > List empty
 * ```
 *
 * @event empty()              Collection was emptied
 * @event set($value, $key)    Item was added to collection
 * @event remove($value, $key) Item was removed from collection
 *
 * @package Kadet\Xmpp\Utils
 */
class ObservableCollection extends \ArrayObject
{
    use BetterEmitter;

    /**
     * Sets the value at the specified index to newval
     * @link  http://php.net/manual/en/arrayobject.offsetset.php
     * @param mixed $index  <p>
     *                      The index being set.
     *                      </p>
     * @param mixed $value <p>
     *                      The new value for the <i>index</i>.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet($index, $value);
        $this->emit('set', [ $value, $index ]);
    }

    /**
     * Unsets the value at the specified index
     * @link  http://php.net/manual/en/arrayobject.offsetunset.php
     * @param mixed $index <p>
     *                     The index being unset.
     *                     </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($index)
    {
        $this->emit('remove', [ $this[$index], $index ]);

        parent::offsetUnset($index);

        if(!count($this)) {
            $this->emit('empty');
        }
    }

    public function __destruct()
    {
        $this->emit('empty');
    }
}
