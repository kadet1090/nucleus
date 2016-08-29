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


use Kadet\Xmpp\Jid;
use Kadet\Xmpp\Stanza\Iq\Query;
use function Kadet\Xmpp\Utils\filter\pass;
use Kadet\Xmpp\Xml\XmlElement;

/**
 * Represents IQ Stanza
 * @package Kadet\Xmpp\Stanza
 *
 * @property Query $query
 */
class Iq extends Stanza
{
    /**
     * Stanza constructor.
     * @param array  $options {
     *     @var Jid     $from      Jid representing "from" stanza attribute
     *     @var Jid     $to        Jid representing "to" stanza attribute
     *     @var string  $id        Unique id, will be generated if omitted
     *     @var string  $type      Stanza type
     *     @var mixed   $content   Stanza content
     *     @var array   $arguments Stanza arguments
     *     @var Query   $query     Query associated with stanza
     * }
     */
    public function __construct(array $options)
    {
        parent::__construct('iq', $options);
    }

    protected function appendChild($element)
    {
        if(count($this->children) > 0) {
            throw new \RuntimeException('Iq stanzas cannot have more than one child.');
        }

        return parent::appendChild($element);
    }

    #region Query
    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->get(Query::class);
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query)
    {
        $this->remove($this->query);
        $this->append($query);
    }
    #endregion

    public static function getXmlCollocations() : array
    {
        return array_merge(
            [[ Query::class, 'name' => pass(), 'uri' => pass() ]],
            parent::getXmlCollocations()
        );
    }
}
