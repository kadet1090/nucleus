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

namespace Kadet\Xmpp\Component;


use Kadet\Highlighter\Utils\Console;
use Kadet\Xmpp\Exception\ReadOnlyException;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Utils\BetterEmitter;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\XmppClient;
use Traversable;
use function Kadet\Xmpp\Utils\helper\format;

/**
 * Class Roster
 * @package Kadet\Xmpp\Component
 *
 * @property-read Iq\Query\Roster\Item[] $items Copy of all roster items
 */
class Roster extends Component implements \IteratorAggregate
{
    use BetterEmitter, Accessors;

    private $_items = [];

    public function setClient(XmppClient $client)
    {
        parent::setClient($client);
        $this->_client->on('init', function(\SplQueue $queue) {
            $queue->enqueue($this->_client->send(new Iq('get', ['query' => new Iq\Query\Roster()])));
        });

        $this->_client->on('iq', function(Iq $iq) {
            /** @var Roster $iq->query */
            switch ($iq->type) {
                case "result":
                    $this->handleResult($iq->query);
                    break;
                case "set":
                    $this->handleSet($iq->query);
                    break;
            }
        }, with\iq\query(Iq\Query\Roster::class));
    }

    private function handleSet(Iq\Query\Roster $query)
    {
        foreach ($query->items as $item) {
            if($item->subscription == 'remove') {
                $this->removeItem($item->jid);
            } else {
                $this->setItem($item);
            }
        }

        $this->emit('update');
    }

    private function handleResult(Iq\Query\Roster $query)
    {
        $this->_client->getLogger()->debug(format('Received roster (version: {version}) update with {no} roster items.', [
            'no' => count($query->items),
            'version' => $query->version
        ]));
        $this->_items = [];
        $this->handleSet($query);
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     * @return boolean true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_items);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return clone ($this->_items[(string)$offset] ?? null);
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        throw new ReadOnlyException('You should not modify roster directly, use update() method.');
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new ReadOnlyException('You should not modify roster directly, use remove() method.');
    }

    public function remove($what)
    {
        $predicate = $what instanceof \Closure ? $what : with\property('jid', with\equals($what));
        $remove    = array_filter($this->_items, $predicate);

        $iq = new Iq('remove', ['query' => new Iq\Query\Roster()]);
        /** @var Iq\Query\Roster\Item $item */
        foreach($remove as $item) {
            $iq->query->append(new Iq\Query\Roster\Item($item->jid, ['subscription' => 'remove']));
        }

        $this->_client->send($iq);
    }

    /**
     * Retrieve an external iterator
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->_items);
    }

    /**
     * @return Iq\Query\Roster\Item[]
     */
    public function getItems()
    {
        return \Kadet\Xmpp\Utils\helper\copy($this->_items);
    }

    public static function group($name)
    {
        return with\property('groups', with\contains($name));
    }

    /**
     * @param callable(Item $item) $mapper
     * @return array
     */
    public function map(callable $mapper)
    {
        return array_map($mapper, $this->items);
    }

    /**
     * @param callable $predicate
     * @return Iq\Query\Roster\Item[]
     */
    public function filter(callable $predicate)
    {
        return array_filter($this->items, $predicate);
    }

    public function asArray() : array
    {
        return $this->items;
    }

    public static function fromArray(array $array)
    {
        // TODO: Implement fromArray() method.
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
        return count($this->items);
    }


    private function setItem(Iq\Query\Roster\Item $item)
    {
        $this->emit('item', [ $item ]);
        $this->_items[(string)$item->jid] = $item;
    }

    private function removeItem(Jid $jid)
    {
        if (!isset($this->_items[(string)$jid])) {
            $this->_client->getLogger()->warning(format('Trying to remove non-existing roster item {jid}', [
                'item' => Console::styled(['color' => 'green'], (string)$jid)
            ]));
            return;
        }

        $this->emit('remove', [ $this->_items[(string)$jid] ]);
        unset($this->_items[(string)$jid]);
    }
}
