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

use Kadet\Xmpp\Utils\filter as with;
use Evenement\EventEmitterTrait;

trait BetterEmitter
{
    use EventEmitterTrait {
        on   as private emitterOn;
        once as private emitterOnce;
    }

    public function on($event, callable $listener, $condition = null)
    {
        $this->emitterOn($event, $this->getCallable($listener, $condition));
    }

    public function once($event, callable $listener, $condition = null)
    {
        $this->emitterOnce($event, $this->getCallable($listener, $condition));
    }

    public function emit($event, array $arguments = [])
    {
        foreach ($this->listeners($event) as $listener) {
            if ($listener(...$arguments) === false) {
                break;
            }
        }
    }

    private function getCallable(callable $listener, $condition) : callable
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

    protected function emitterResolveCondition($condition) : callable
    {
        if (is_callable($condition)) {
            return $condition;
        } elseif (class_exists($condition)) {
            return with\typeof($condition);
        } else {
            throw new \Exception(); // todo: exception
        }
    }
}
