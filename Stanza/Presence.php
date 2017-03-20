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

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Presence\Show;
use Kadet\Xmpp\Utils\filter;
use Kadet\Xmpp\Xml\XmlElement;

use function Kadet\Xmpp\Utils\helper\format;

/**
 * Represents Presence Stanza
 *
 * @package Kadet\Xmpp\Stanza
 *
 * @property string $show     Presence show type, specifying kind of contact availability.
 *                            One of: available, unavailable, chat, dnd, away, xa.
 * @property string $status   Presence status message.
 * @property int    $priority Presence priority
 */
class Presence extends Stanza
{
    /**
     * Presence constructor.
     * @param array $options {
     *     @var Jid    $from    Jid representing "from" stanza attribute
     *     @var Jid    $to      Jid representing "to" stanza attribute
     *     @var string $id      Unique id, will be generated if omitted
     *     @var string $type    Stanza type
     *     @var string $show
     *     @var string status
     *     @var int    $priority
     * }
     */
    public function __construct(array $options = [])
    {
        parent::__construct('presence', $options);
    }

    /**
     * @return null|string
     */
    public function getShow()
    {
        return (string)$this->element('show', 'jabber:client')->innerXml // return show node content if exist
            ?? (in_array($this->type, ['available', 'unavailable']) ? $this->type : null); // or type if available
    }

    /**
     * Sets presence show. If $show is "available" or "unavailable" it sets presence type to match show kind.
     *
     * @param string $show Desired show type.
     */
    public function setShow(string $show = 'available')
    {
        if(!Show::valid($show)) {
            throw new InvalidArgumentException(format('$show must be one of: {possible}. {show} given.', [
                'possible' => implode(', ', Show::available()),
                'show'     => $show
            ]));
        }

        $predicate = filter\element('show', 'jabber:client');
        if(in_array($show, ['available', 'unavailable'])) {
            $this->remove($predicate);
            $this->type = $show;
            return;
        }

        ($this->get($predicate) ?: $this->append(new XmlElement('show', 'jabber:client')))->setContent($show);
    }

    /**
     * Gets presence status message.
     *
     * @return null|string
     */
    public function getStatus()
    {
        return (string)$this->element('status') ?? null;
    }

    public function setStatus(string $status = null)
    {
        $predicate = filter\element('status', 'jabber:client');
        if($status === null) {
            $this->remove($predicate);
            return;
        }

        ($this->get($predicate) ?: $this->append(new XmlElement('status', 'jabber:client')))->setContent($status);
    }

    public function getPriority()
    {
        return (int)$this->element('priority') ?? null;
    }

    public function setPriority(int $priority = null)
    {
        $predicate = filter\element('status', 'jabber:client');
        if(!$priority) {
            $this->remove($predicate);
        }

        $element = $this->has($predicate)
            ? $this->element('status', 'jabber:client')
            : $this->append(new XmlElement('priority', 'jabber:client'));

        $element->setContent($priority);
    }

    public static function show(string $show, string $status = null, array $options = [])
    {
        return new self(array_merge($options, [
            'show' => $show,
            'status' => $status,
        ]));
    }
}