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


use Kadet\Xmpp\Utils\StreamDecorator;
use React\Stream\DuplexStreamInterface;
use React\Stream\ThroughStream;

class StreamDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function eventProvider()
    {
        return [
            ['data'],
            ['end'],
            ['drain'],
            ['error'],
            ['close'],
        ];
    }

    public function methodProvider()
    {
        return [
            'isReadable' => ['isReadable', []],
            'isWritable' => ['isWritable', []],
            'pause'      => ['pause', []],
            'resume'     => ['resume', []],
            'write'      => ['write', ['data']],
            'end'        => ['end', ['data']],
            'close'      => ['close', []],
            'pipe'       => ['pipe', [ new ThroughStream() ]]
        ];
    }

    /**
     * @uses React\Stream\ThroughStream
     */
    public function testStreamExchange()
    {
        $foo = new ThroughStream();
        $bar = new ThroughStream();

        /** @var StreamDecorator $decorator */
        $decorator = $this->getStreamMock($foo);
        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();
        $mock->expects($this->once())->method('callback')->with('bar');


        $decorator->on('data', [$mock, 'callback']);
        $decorator->exchangeStream($bar);

        $foo->write('foo');
        $bar->write('bar');
    }

    /**
     * @dataProvider eventProvider
     * @uses React\Stream\ThroughStream
     */
    public function testStreamRedirectsEvents($event)
    {
        $decorated = new ThroughStream();

        /** @var StreamDecorator $decorator */
        $decorator = $this->getStreamMock($decorated);
        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();
        $mock->expects($this->once())->method('callback')->withAnyParameters();


        $decorator->on($event, [$mock, 'callback']);
        $decorated->emit($event, []);
    }

    /**
     * @dataProvider eventProvider
     * @uses React\Stream\ThroughStream
     */
    public function testStreamExchangedRedirectsEvents($event)
    {
        $foo = new ThroughStream();
        $bar = new ThroughStream();

        /** @var StreamDecorator $decorator */
        $decorator = $this->getStreamMock($foo);

        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();
        $mock->expects($this->once())->method('callback')->with('bar');

        $decorator->exchangeStream($bar);

        $decorator->on($event, [$mock, 'callback']);
        $bar->emit($event, ['bar']);
        $foo->emit($event, ['foo']);
    }

    /**
     * @param       $method
     * @param array $arguments
     *
     * @dataProvider methodProvider
     */
    public function testRedirection($method, $arguments = [])
    {
        $decorated = $this->createMock(ThroughStream::class);
        $decorated->expects($this->once())->method($method)->with(...$arguments);

        $this->getStreamMock($decorated)->$method(...$arguments);
    }

    /**
     * @param DuplexStreamInterface $decorated
     * @return \PHPUnit_Framework_MockObject_MockObject|StreamDecorator
     */
    private function getStreamMock(DuplexStreamInterface $decorated)
    {
        return $this->getMockForAbstractClass(StreamDecorator::class, [ $decorated ]);
    }
}
