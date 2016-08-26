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

namespace Kadet\Xmpp\Tests;


use Kadet\Xmpp\Utils\BetterEmitter;

/**
 * Class BetterEmitterTest
 * @package Kadet\Xmpp\Tests
 *
 * @covers \Kadet\Xmpp\Utils\BetterEmitter
 */
class BetterEmitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return BetterEmitter
     */
    public function getEmitter()
    {
        return $this->getObjectForTrait(BetterEmitter::class);
    }

    public function testEventSubscription()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->once())->method('listener')->with('foo', 1, []);

        $emitter = $this->getEmitter();
        $emitter->on('event', [$mock, 'listener']);
        $emitter->emit('event', ['foo', 1, []]);
    }

    public function testEventFiltering()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener', 'predicate'])->getMock();
        $mock->expects($this->exactly(2))->method('listener')->withConsecutive(['foo'], ['bar']);
        $mock->expects($this->exactly(3))->method('predicate')
            ->withConsecutive(['foo'], ['nope'], ['bar'])
            ->willReturnOnConsecutiveCalls(true, false, true);

        $emitter = $this->getEmitter();
        $emitter->on('event', [$mock, 'listener'], function(...$a) use($mock) { return $mock->predicate(...$a); });

        $emitter->emit('event', ['foo']);
        $emitter->emit('event', ['nope']);
        $emitter->emit('event', ['bar']);
    }

    public function testEventSubscriptionOnce()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->exactly(2))->method('listener')->with('foo', 1, []);

        $emitter = $this->getEmitter();
        $emitter->once('event', [$mock, 'listener']);
        $emitter->emit('event', ['foo', 1, []]);
        $emitter->emit('event', ['foo', 1, []]);
    }

    public function testEventFilteringOnce()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener', 'predicate'])->getMock();
        $mock->expects($this->once())->method('listener')->with('bar');
        $mock->expects($this->exactly(2))->method('predicate')
            ->withConsecutive(['foo'], ['bar'], ['nope'])
            ->willReturnOnConsecutiveCalls(false, true);

        $emitter = $this->getEmitter();
        $emitter->once('event', [$mock, 'listener'], function(...$a) use($mock) { return $mock->predicate(...$a); });

        $emitter->emit('event', ['foo']);
        $emitter->emit('event', ['bar']);
        $emitter->emit('event', ['nope']);
    }

    public function testEventSubscriptionCancel()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->once())->method('listener')->with('foo', 1, []);

        $emitter = $this->getEmitter();
        $emitter->on('event', [$mock, 'listener']);
        $emitter->emit('event', ['foo', 1, []]);
        $emitter->removeListener('event', [$mock, 'listener']);
        $emitter->emit('event', ['foo', 1, []]);
    }

    public function testEventSubscriptionCancelWithPredicate()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->once())->method('listener')->with('foo', 1, []);

        $emitter = $this->getEmitter();
        $event = $emitter->on('event', [$mock, 'listener']);
        $emitter->emit('event', ['foo', 1, []]);
        $emitter->removeListener('event', $event);
        $emitter->emit('event', ['foo', 1, []]);
    }

    public function testReference()
    {
        $emitter = $this->getEmitter();

        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->once())->method('listener')->with($emitter, 'foo', 1, []);

        $emitter->on('event', $emitter->reference([$mock, 'listener']));
        $emitter->emit('event', ['foo', 1, []]);
    }

    public function testEventEmittingOnException()
    {
        $exception = new \Exception();
        $emitter = $this->getEmitter();

        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener', 'exception'])->getMock();
        $mock->expects($this->once())->method('listener')->willThrowException($exception);
        $mock->expects($this->once())->method('exception')->with($exception)->willReturn(false);

        $emitter->on('event', [$mock, 'listener']);
        $emitter->on('exception', [$mock, 'exception']);
        $emitter->emit('event', []);
    }

    /**
     * @expectedException \Exception
     */
    public function testException()
    {
        $exception = new \Exception();
        $emitter = $this->getEmitter();

        $mock = $this->getMockBuilder('stdClass')->setMethods(['listener'])->getMock();
        $mock->expects($this->once())->method('listener')->willThrowException($exception);

        $emitter->on('event', [$mock, 'listener']);
        $emitter->emit('event', []);
    }
}
