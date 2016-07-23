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

namespace Kadet\Xmpp\Tests;


use Kadet\Xmpp\Utils\PriorityCollection;

class PriorityCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testPriorities()
    {
        $collection = new PriorityCollection();
        $collection->insert(0, 0);
        $collection->insert(1, 1);
        $collection->insert(-1, -1);

        $this->assertEquals([1, 0, -1], iterator_to_array($collection));
    }

    public function testRemoval()
    {
        $collection = new PriorityCollection();
        $collection->insert(0, 0);
        $collection->insert(-1, -1);
        $collection->insert(-1, 1);
        $collection->remove(-1);

        $this->assertEquals([0], iterator_to_array($collection));
    }

    public function testCount()
    {
        $collection = new PriorityCollection();
        $this->assertEquals(0, count($collection));
        $collection->insert(0, 0);
        $collection->insert(-1, -1);
        $this->assertEquals(2, count($collection));
    }
}
