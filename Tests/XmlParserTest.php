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
use Kadet\Xmpp\Xml\XmlFactoryCollocations;
use Kadet\Xmpp\Xml\XmlParser;
class FooElement extends XmlElement {}
class XmlElementWithCollocations extends XmlElement implements XmlFactoryCollocations
{
    public static function getXmlCollocations() : array
    {
        return [
             [FooElement::class, "name" => "foo", "uri" => "uri:xmlns:foo"]
        ];
    }
}

/**
 * Class XmlParserTest
 * @package Kadet\Xmpp\Tests
 *
 * @covers \Kadet\Xmpp\Xml\XmlParser
 * @covers \Kadet\Xmpp\Xml\XmlElementFactory
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
        $this->_parser->parse("<stream:stream xmlns='jabber:client'>");
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

        $this->_parser = new XmlParser(new XmlElementFactory());
        $this->_parser->on('parse.begin', $callback);
        $this->_parser->on('parse.end', $callback);
        $this->_parser->parse("<root></root>");
    }

    public function testXmlElementCollocations()
    {
        list($callback, $mock) = $this->getMockForCallback($this->exactly(1));
        $mock->with($this->callback(function(XmlElement $element) {
            return $element->element('foo') instanceof FooElement;
        }));

        $this->_parser->factory->register(XmlElementWithCollocations::class, 'uri:xmlns:foo');
        $this->_parser->on('element', $callback);

        $this->_parser->parse("<bar xmlns='uri:xmlns:foo'><foo /></bar>");
    }

    public function getMockForCallback($matcher) {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['callback'])->getMock();
        return [[$mock, 'callback'], $mock->expects($matcher)->method('callback')];
    }
}
