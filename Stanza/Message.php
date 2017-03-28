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

namespace Kadet\Xmpp\Stanza;


use Kadet\Xmpp\Stanza\Message\Body;
use Kadet\Xmpp\Xml\XmlElement;
use function Kadet\Xmpp\Utils\filter\all;
use function Kadet\Xmpp\Utils\filter\element\{
    attribute, name
};

class Message extends Stanza
{
    /**
     * Message constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct('message', $options);
    }

    public function getBody($language = null)
    {
        return $this->get($this->bodyPredicate($language))->innerXml ?? null;
    }

    public function setBody($content, $language = null)
    {
        $body = $this->get($this->bodyPredicate($language))
             ?: $this->append(new Body($language)); // todo: Body class

        $body->setContent($content);
    }

    private function bodyPredicate($language) {
        $predicate = name('body');
        if($language !== null) {
            $predicate = all($predicate, attribute('lang', $language, XmlElement::XML));
        }

        return $predicate;
    }
}