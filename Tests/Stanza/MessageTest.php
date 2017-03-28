<?php
/**
 * Nucleus - XMPP Library for PHP
 *
 * Copyright (C) 2017, Some rights reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Xmpp\Tests\Stanza;

use Kadet\Xmpp\Stanza\Message;
use Kadet\Xmpp\Xml\XmlElement;
use PHPUnit\Framework\TestCase;
use function Kadet\Xmpp\Utils\filter\element\name;

class MessageTest extends TestCase
{
    /** @var Message */
    private $_message;

    protected function setUp()
    {
        $this->_message = new Message();
    }

    public function testConstructor()
    {
        $this->assertEquals('message', $this->_message->localName);
    }

    public function testBodySetting()
    {
        $this->_message->body = "lorem";

        $this->assertCount(1, $this->_message->children);
        $this->assertEquals('lorem', $this->_message->get(name('body'))->innerXml);
        $this->assertFalse($this->_message->hasAttribute('lang', XmlElement::XML));
    }

    public function testBodySettingWithLanguage()
    {
        $this->_message->setBody('lorem', 'en');

        $this->assertEquals('en', $this->_message->get(name('body'))->getAttribute('lang', XmlElement::XML));
    }

    public function testObtainingBodyContent()
    {
        $this->_message->append(new XmlElement('body', null, ['content' => 'lorem']));

        $this->assertEquals('lorem', $this->_message->body);
    }
}
