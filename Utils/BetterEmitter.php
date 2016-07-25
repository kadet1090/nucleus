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

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Utils\filter as with;
use Evenement\EventEmitterTrait;

trait BetterEmitter
{
    /**
     * @var PriorityCollection[]
     */
    protected $listeners;

    use EventEmitterTrait;

    public function on($event, callable $listener, $condition = null, int $priority = 0)
    {
        $this->addListener($event, $this->getConditionalCallable($listener, $condition), $priority);
    }

    public function once($event, callable $listener, $condition = null, int $priority = 0)
    {
        $this->on($event, $this->getOnceCallable($listener, $event), $condition, $priority);
    }

    public function removeListener($event, callable $listener)
    {
        if(!isset($this->listeners[$event])) {
            return false;
        }

        $this->listeners[$event]->remove($listener);
        return true;
    }

    public function emit($event, array $arguments = [])
    {
        foreach ($this->listeners($event) as $listener) {
            if ($listener(...$arguments) === false) {
                return false;
            }
        }

        return true;
    }

    public function reference(callable $callable, int $position = 0) : callable
    {
        return \Kadet\Xmpp\Utils\helper\partial($callable, $this, $position);
    }

    private function addListener($event, callable $listener, int $priority = 0)
    {
        if(!isset($this->listeners[$event])) {
            $this->listeners[$event] = new PriorityCollection();
        }

        $this->listeners[$event]->insert($listener, $priority);
    }

    private function getConditionalCallable(callable $listener, $condition) : callable
    {
        if ($condition === null) {
            return $listener;
        }

        $condition = $this->emitterResolveCondition($condition);
        return function (...$arguments) use ($listener, $condition) {
            if ($condition(...$arguments)) {
                $listener(...$arguments);
            }
        };
    }

    private function getOnceCallable(callable $listener, $event) : callable
    {
        return $onceListener = function (...$arguments) use (&$onceListener, $event, $listener) {
            $this->removeListener($event, $onceListener);

            $listener(...$arguments);
        };
    }

    protected function emitterResolveCondition($condition) : callable
    {
        if (is_callable($condition)) {
            return $condition;
        } elseif (class_exists($condition)) {
            return with\ofType($condition);
        } else {
            throw new InvalidArgumentException('$condition must be either class-name or callable, ' . helper\typeof($condition) . ' given.');
        }
    }
}
