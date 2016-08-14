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


use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XmlElementFactory;
use Kadet\Xmpp\Xml\XmlParser;

/**
 * Class XmlParserTest
 * @package Kadet\Xmpp\Tests
 *
 * @covers \Kadet\Xmpp\Xml\XmlParser
 */
class XmlParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var XmlParser */
    private $_parser;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_parser = new XmlParser(new XmlElementFactory());
    }

    public function testFactoryCalling()
    {
        /** @var XmlElementFactory|\PHPUnit_Framework_MockObject_MockObject $factory */
        $factory = $this
            ->getMockBuilder(XmlElementFactory::class)
            ->setMethods(['create'])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $factory->expects($this->once())->method('create')->with('urn:xmlns:namespace', 'element');

        $this->_parser = new XmlParser($factory);
        $this->_parser->parse("<element xmlns='urn:xmlns:namespace'/>");
    }

    public function testRootElementEndHandling()
    {
        list($callback, $mock) = $this->getMockForCallback($this->exactly(2));
        $mock->with($this->isInstanceOf(XmlElement::class));

        $this->_parser->on('parse.begin', $callback);
        $this->_parser->on('parse.end', $callback);
        $this->_parser->parse("<root></root>");
    }

    public function getMockForCallback($matcher) {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();
        return [[$mock, 'callback'], $mock->expects($matcher)->method('callback')];
    }
}
