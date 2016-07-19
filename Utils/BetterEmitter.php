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


use Evenement\EventEmitterTrait;

trait BetterEmitter
{
    use EventEmitterTrait {
        on   as private emitterOn;
    }

    public function on($event, callable $listener, $condition = null)
    {
        if($condition !== null) {
            $callable = $listener;
            $condition = $this->resolveCondition($condition);

            $listener = function(...$arguments) use ($callable, $condition) {
                if($condition(...$arguments)) {
                    $callable(...$arguments);
                }
            };
        }

        $this->emitterOn($event, $listener);
    }

    public function emit($event, array $arguments = [])
    {
        foreach ($this->listeners($event) as $listener) {
            if($listener(...$arguments) === false) {
                break;
            }
        }
    }

    private function resolveCondition($condition) : callable
    {
        if(is_callable($condition)) {
            return $condition;
        } else {
            throw new \Exception(); // todo: exception
        }
    }
}
