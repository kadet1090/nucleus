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


use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Xml\XPathQuery;

class XmlElementTest extends \PHPUnit_Framework_TestCase
{
    const XMLNS = 'urn:some:xmlns';

    public function elementProvider()
    {
        return [
            ['tag', null, null],
            ['tag', self::XMLNS, null],
            ['tag', self::XMLNS, null],
            ['tag', self::XMLNS, 'prefix'],
        ];
    }

    /**
     * @param $name
     * @param $xmlns
     * @param $prefix
     *
     * @dataProvider elementProvider
     */
    public function testCreatesPlainElement($name, $xmlns, $prefix)
    {
        $element = XmlElement::plain($prefix ? "$prefix:$name" : $name, $xmlns);
        $this->assertEquals($name,   $element->localName);
        $this->assertEquals($xmlns,  $element->namespace);
        $this->assertEquals($prefix, $element->prefix);

        $this->assertEquals($prefix ? "$prefix:$name" : $name, $element->name);

        $this->assertEmpty($element->children);
        $this->assertEmpty($element->attributes);
    }

    public function testArgumentsAdding()
    {
        $element = new XmlElement('tag');
        $element->setAttribute('attr', 'value');

        $this->assertTrue($element->hasAttribute('attr'));

        $this->assertEquals('value', $element->getAttribute('attr'));
        $this->assertEquals(['attr' => 'value'], $element->attributes);
    }

    public function testArgumentsAddingWithNamespace()
    {
        $element = new XmlElement('tag');
        $element->setNamespace(self::XMLNS, 'prefix');
        $element->setAttribute('attr', 'value', self::XMLNS);

        $this->assertTrue($element->hasAttribute('attr', self::XMLNS));
        $this->assertTrue($element->hasAttribute('prefix:attr'));

        $this->assertEquals('value', $element->getAttribute('attr', self::XMLNS));
        $this->assertEquals('value', $element->getAttribute('prefix:attr'));
        $this->assertEquals(['prefix:attr' => 'value'], $element->attributes);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testArgumentsAddingWithWrongNamespace()
    {
        $element = new XmlElement('tag');
        $element->setAttribute('attr', 'value', self::XMLNS);


        $this->assertTrue($element->hasAttribute('attr', self::XMLNS));
        $this->assertTrue($element->hasAttribute('prefix:attr'));

        $this->assertEquals('value', $element->getAttribute('attr', self::XMLNS));
        $this->assertEquals('value', $element->getAttribute('prefix:attr'));
        $this->assertEquals(['prefix:attr' => 'value'], $element->attributes);
    }

    public function testAddingChild()
    {
        $parent = new XmlElement('parent');
        $child  = new XmlElement('child');

        $parent->append($child);

        $this->assertEquals($parent, $child->parent);
        $this->assertEquals([ $child ], $parent->children);
    }

    public function testAddingChildWithNamespace()
    {
        $parent = new XmlElement('parent', self::XMLNS);
        $child  = new XmlElement('child');

        $parent->append($child);

        $this->assertEquals(self::XMLNS, $child->namespace);
    }

    public function testAddingChildWithOtherNamespace()
    {
        $parent = new XmlElement('parent', self::XMLNS);
        $child  = new XmlElement('child', 'some:other:xmlns');

        $parent->append($child);

        $this->assertEquals('some:other:xmlns', $child->namespace);
    }

    public function testAddingChildWithPrefix()
    {
        $parent = new XmlElement('parent');
        $parent->setNamespace(self::XMLNS, 'prefix');
        $child  = new XmlElement('prefix:child');

        $parent->append($child);

        $this->assertEquals(self::XMLNS, $child->namespace);
    }

    public function testPrefixAddingChildWithNamespace()
    {
        $parent = new XmlElement('parent');
        $parent->setNamespace(self::XMLNS, 'prefix');
        $child  = new XmlElement('child', self::XMLNS);

        $parent->append($child);
        $this->assertEquals('prefix', $child->prefix);
    }

    public function testAddingContent()
    {
        $parent = new XmlElement('parent');
        $parent->append("Some text");

        $this->assertEquals("Some text", $parent->innerXml);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddingInvalidContent()
    {
        $parent = new XmlElement('parent');
        $parent->append([]);
    }

    public function testXmlOutput()
    {
        $parent = new XmlElement('parent', self::XMLNS);
        $parent->setAttribute('test', 'value');
        $parent->setNamespace('urn:prefix', 'prefix');

        $child = new XmlElement('child', 'urn:other:xmlns');
        $parent->append($child);

        $child->setAttribute('smth', 'value', 'urn:prefix');
        $child->append(new XmlElement('smth'));

        $xml = <<<XML
<parent xmlns="urn:some:xmlns" xmlns:prefix="urn:prefix" test="value">
    <child xmlns="urn:other:xmlns" prefix:smth="value">
        <smth/>
    </child>
</parent>
XML;

        $this->assertXmlStringEqualsXmlString($xml, (string)$parent);
    }

    public function testElementFiltering()
    {
        $parent = new XmlElement('parent');
        $parent->append($foo = new XmlElement('foo'));
        $parent->append($foobar = new XmlElement('foobar'));
        $parent->append($bar = new XmlElement('bar', 'urn:bar'));

        $this->assertEquals($foo, $parent->element('foo'));
        $this->assertEquals($bar, $parent->element('bar', 'urn:bar'));
    }


    public function testElementsFiltering()
    {
        $parent = new XmlElement('parent');
        $parent->append($foo1 = new XmlElement('foo'));
        $parent->append($foo2 = new XmlElement('foo'));

        $this->assertEquals([ $foo1, $foo2 ], $parent->elements('foo'));
    }

    public function testElementFinding()
    {
        $parent = new XmlElement('parent');
        $parent->append($foo = new XmlElement('foo'));
        $parent->append($foobar = new XmlElement('foobar'));
        $parent->append($bar = new XmlElement('bar', 'urn:bar'));

        $this->assertEquals($foo, $parent->get(function(XmlElement $e) { return $e->localName === 'foo'; }));
        $this->assertFalse($parent->get(function(XmlElement $e) { return false; }));
    }

    public function testQuery()
    {
        $this->assertInstanceOf(XPathQuery::class, (new XmlElement('parent'))->query('//'));
    }
}
