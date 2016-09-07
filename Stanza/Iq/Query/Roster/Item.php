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

namespace Kadet\Xmpp\Stanza\Iq\Query\Roster;


use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Xml\XmlElement;

/**
 * Class Item
 * @package Kadet\Xmpp\Stanza\Iq\Query\Roster
 *
 * @property bool $approved
 * @property string $ask
 * @property Jid $jid
 * @property string $name
 * @property string $subscription
 * @property string[] $groups
 */
class Item extends XmlElement
{
    public function __construct(Jid $jid, array $options = [])
    {
        $this->jid = $jid;
        parent::__construct('item', null, $options);
    }

    #region Approved
    /**
     * @return bool
     */
    public function getApproved(): bool
    {
        return $this->getAttribute('approved') === 'true';
    }

    /**
     * @param bool $approved
     */
    public function setApproved(bool $approved)
    {
        $this->setAttribute('approved', $approved ? 'true' : 'false');
    }
    #endregion

    #region Ask
    /**
     * @return string
     */
    public function getAsk(): string
    {
        return $this->getAttribute('ask');
    }

    /**
     * @param string $ask
     */
    public function setAsk(string $ask)
    {
        $this->setAttribute('ask', $ask);
    }
    #endregion

    #region Jid
    /**
     * @return Jid
     */
    public function getJid(): Jid
    {
        return new Jid($this->getAttribute('jid'));
    }

    /**
     * @param Jid $jid
     */
    public function setJid(Jid $jid)
    {
        $this->setAttribute('jid', (string)$jid);
    }
    #endregion

    #region Name
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->setAttribute('name', $name);
    }
    #endregion

    #region Subscription
    /**
     * @return string
     */
    public function getSubscription(): string
    {
        return $this->getAttribute('subscription');
    }

    /**
     * @param string $subscription
     */
    public function setSubscription(string $subscription)
    {
        $this->setAttribute('subscription', $subscription);
    }
    #endregion

    #region Groups
    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return array_map(function(XmlElement $element) {
            return $element->innerXml;
        }, $this->elements('group', null) ?: []);
    }

    public function setGroups(array $groups)
    {
        $this->remove(\Kadet\Xmpp\Utils\filter\element\name('group'));
        $this->append($groups);
    }
    #endregion
}
