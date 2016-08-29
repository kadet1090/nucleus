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

namespace Kadet\Xmpp\Stanza\Iq\Query;


use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Stanza\Iq\Query;
use Kadet\Xmpp\Stanza\Iq\Query\Roster\Item;
use function \Kadet\Xmpp\Utils\helper\format;
use Kadet\Xmpp\Xml\XmlFactoryCollocations;

/**
 * Class Roster
 * @package Kadet\Xmpp\Stanza\Iq\Query
 *
 * @property string $version Roster version (if server supports)
 * @property Query\Roster\Item[] $items Roster version (if server supports)
 */
class Roster extends Query implements XmlFactoryCollocations
{
    /**
     * Query constructor
     *
     * @param array  $options    {
     *     @var mixed               $content    Content of element
     *     @var array               $attributes Element attributes
     *     @var array               string      $version Roster version (if server supports)
     *     @var Query\Roster\Item[] $items      Roster version (if server supports)
     * }
     */
    public function __construct(array $options = [])
    {
        parent::__construct('jabber:iq:roster', 'query', $options);
    }


    #region Version
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getAttribute('ver');
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version)
    {
        $this->setAttribute('ver', $version);
    }
    #endregion

    #region Items
    /**
     * @return Query\Roster\Item[]
     */
    public function getItems(): array
    {
        return $this->all(Item::class);
    }

    /**
     * @param Query\Roster\Item[] $items
     */
    public function setItems(array $items)
    {
        $this->remove(\Kadet\Xmpp\Utils\filter\instance(Item::class));
        $this->append($items);
    }
    #endregion

    protected function appendChild($element)
    {
        if(!$element instanceof Item) {
            throw new InvalidArgumentException(format('Only instances of roster item ({expected}) can be added into roster, tried to add {actual}', [
                'expected' => Item::class,
                'actual'   => get_class($element)
            ]));
        }

        return parent::appendChild($element);
    }

    public static function getXmlCollocations() : array
    {
        return [
            [ Item::class, 'name' => 'item', 'uri' => 'jabber:iq:roster' ]
        ];
    }
}
