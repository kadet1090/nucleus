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

namespace Kadet\Xmpp\Stanza;


use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Exception\NotImplementedException;
use Kadet\Xmpp\Xml\XmlElement;
use Kadet\Xmpp\Utils\filter;

use function Kadet\Xmpp\Utils\helper\format;

/**
 * Class Error
 * @package Kadet\Xmpp\Stanza
 * @see     http://xmpp.org/rfcs/rfc6120.html#stanzas-error
 *
 * @property string $type      Error type, describes how to deal with event
 * @property string $by        Error generator name
 * @property string $condition Error defined condition, equivalent of code
 * @property string $text      Textual description of error
 */
class Error extends XmlElement
{
    const XMLNS = 'urn:ietf:params:xml:ns:xmpp-stanzas';

    /**
     * @return string
     */
    public function getBy(): string
    {
        return $this->getAttribute('by');
    }

    /**
     * @param string $by
     */
    public function setBy(string $by)
    {
        $this->setAttribute('by');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->getAttribute('type');
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        if(!in_array($type, ['auth', 'cancel', 'continue', 'modify', 'wait'])) {
            throw new InvalidArgumentException(
                format("Error type must be 'auth', 'cancel', 'continue', 'modify' or 'wait', '{type}' given.", [
                    'type' => $type
                ])
            );
        }

        $this->setAttribute('type');
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->get(filter\all(
            filter\element\xmlns(self::XMLNS),
            filter\not(filter\element\name('text'))
        ))->localName;
    }

    /**
     * @param string $condition
     */
    public function setCondition(string $condition)
    {
        throw new NotImplementedException("Condition setting awaits for implementation"); // todo: implement
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return (string)$this->get(filter\all(
            filter\element\xmlns(self::XMLNS),
            filter\element\name('text')
        ));
    }

    /**
     * @param string $text
     */
    public function setText(string $text)
    {
        if(!$this->text) {
            $this->append(new XmlElement('text', self::XMLNS));
        }

        $this->get(filter\all(
            filter\element\xmlns('urn:ietf:params:xml:ns:xmpp-stanzas'),
            filter\element\name('text')
        ))->innerXml = $text;
    }

    /**
     * XmlElement constructor
     * @param string|XmlElement $condition
     * @param string $description
     * @param array  $options
     */
    public function __construct(string $condition, string $description = null, array $options = [])
    {
        $content = [
            !$condition instanceof XmlElement ? $this->_definedCondition($condition) : $condition
        ];

        if($description !== null) {
            $content[] = new XmlElement('text', self::XMLNS, ['content' => $description]);
        }

        parent::__construct('error', null, array_merge_recursive($options, [
            'content' => $content
        ]));
    }

    private function _definedCondition(string $condition) : XmlElement
    {
        if(!in_array($condition, [
            'bad-request', 'conflict', 'feature-not-implemented', 'forbidden',
            'gone', 'internal-server-error', 'item-not-found', 'jid-malformed',
            'not-acceptable', 'not-allowed', 'not-authorized', 'policy-violation',
            'recipient-unavailable', 'redirect', 'registration-required',
            'remote-server-not-found', 'remote-server-timeout', 'resource-constraint',
            'service-unavailable', 'subscription-required', 'undefined-condition'
        ])) {
            throw new InvalidArgumentException('Condition must be one of conditions specified by RFC 6120: http://xmpp.org/rfcs/rfc6120.html#stanzas-error-conditions');
        }

        return new XmlElement($condition, self::XMLNS);
    }
}
