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
        return $this->addListener($event, $this->getConditionalCallable($listener, $condition), $priority);
    }

    public function once($event, callable $listener, $condition = null, int $priority = 0)
    {
        return $this->on($event, $this->getOnceCallable($this->getConditionalCallable($listener, $condition), $event), null, $priority);
    }

    public function removeListener($event, callable $listener)
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }

        $this->listeners[$event]->remove($listener);

        return true;
    }

    public function emit($event, array $arguments = [])
    {
        foreach ($this->listeners($event) as $listener) {
            try {
                if ($listener(...$arguments) === false) {
                    return false;
                }
            } catch (\Throwable $exception) {
                if($this->emit('exception', [ $exception, $event ])) {
                    throw $exception;
                }
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
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = new PriorityCollection();
        }

        $this->listeners[$event]->insert($listener, $priority);

        return $listener;
    }

    private function getConditionalCallable(callable $listener, $condition) : callable
    {
        if ($condition === null) {
            return $listener;
        }

        $condition = with\predicate($condition);

        return function (...$arguments) use ($listener, $condition) {
            if ($condition(...$arguments)) {
                return $listener(...$arguments);
            }

            return null;
        };
    }

    private function getOnceCallable(callable $listener, $event) : callable
    {
        return $onceListener = function (...$arguments) use (&$onceListener, $event, $listener) {
            if(($result = $listener(...$arguments)) !== null) {
                $this->removeListener($event, $onceListener);
                return $result;
            }

            return null;
        };
    }
}
