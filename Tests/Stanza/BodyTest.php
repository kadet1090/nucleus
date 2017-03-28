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

namespace Kadet\Xmpp\Tests\Stanza\Message;

use Kadet\Xmpp\Stanza\Message\Body;
use Kadet\Xmpp\Xml\XmlElement;

class BodyTest extends \PHPUnit_Framework_TestCase
{
    /** @var Body */
    private $_body;

    protected function setUp()
    {
        $this->_body = new Body();
    }

    public function testLanguageSetting()
    {
        $this->_body->language = "en";

        $this->assertEquals('en', $this->_body->getAttribute('lang', XmlElement::XML));
    }

    public function testLanguageObtaining()
    {
        $this->_body->setAttribute('lang', 'en', XmlElement::XML);

        $this->assertEquals('en', $this->_body->language);
    }

    public function testToString()
    {
        $this->_body->setContent('lorem ipsum');

        $this->assertEquals('lorem ipsum', (string)$this->_body);
    }
}
