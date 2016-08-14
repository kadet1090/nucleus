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

use Kadet\Highlighter\Utils\Console;

class Dumper
{
    use Singleton;

    /** @var callable[string] */
    private $_dumpers;

    public function register(string $class, callable $dumper)
    {
        $this->_dumpers[$class] = $dumper;
    }

    public function dump($value)
    {
        return trim(($this->getDumper($value))($value));
    }

    private function getDumper($value)
    {
        if (is_object($value)) {
            $class = get_class($value);
            foreach (array_merge([$class], class_parents($class), class_implements($class)) as $class) {
                if (isset($this->_dumpers[$class])) {
                    return $this->_dumpers[$class];
                }
            }
        }

        if (isset($this->_dumpers[gettype($value)])) {
            return $this->_dumpers[gettype($value)];
        }

        return [$this, '_dumpDefault'];
    }

    private function _dumpDefault($value)
    {
        return helper\format('{type} {value}', [
            'type'  => Console::get()->styled(['color' => 'red'], \Kadet\Xmpp\Utils\helper\typeof($value)),
            'value' => var_export($value, true)
        ]);
    }

    private function _dumpObject($value)
    {
        ob_start();
        var_dump($value);

        return trim(ob_get_flush());
    }

    private function _dumpArray(array $array)
    {
        $console = Console::get();

        $result = $console->styled(['color' => 'yellow'], 'array').' with '.$console->styled(['color' => 'magenta'], count($array)).' elements:'.PHP_EOL;
        foreach ($array as $key => $value) {
            $result .= "\t".str_replace("\n", "\n\t", '['.$this->dump($key).']: '.$this->dump($value)).PHP_EOL;
        }

        return $result;
    }

    public function init()
    {
        $this->register('array',  [$this, '_dumpArray']);
        $this->register('object', [$this, '_dumpObject']);
    }
}
