<?php declare (strict_types = 1);
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

namespace Kadet\Xmpp;

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Utils\Immutable;

/**
 * Class Jid
 * @package Kadet\Xmpp
 *
 * @property-read string $domain
 * @property-read string $local
 * @property-read string $resource
 */
class Jid implements Immutable
{
    use Accessors;

    private $_local;

    /**
     * @var string
     */
    private $_domain;

    /**
     * @var string|null
     */
    private $_resource;

    public function __construct(string $address, string $local = null, string $resource = null)
    {
        if ($local === null && $resource === null) {
            list($address, $local, $resource) = self::_split($address);
        }

        self::validate($address, $local, $resource);

        $this->_domain   = $address;
        $this->_local    = $local;
        $this->_resource = $resource;
    }

    private static function _split(string $address)
    {
        preg_match('#^(?:(?P<local>[^@]+)@)?(?P<host>.*?)(?:/(?P<resource>.+?))?$#i', $address, $result);

        return [$result['host'], $result['local'] ?: null, $result['resource'] ?? null];
    }

    /**
     * Validates address and throws InvalidArgumentException in case of failure.
     *
     * @param string      $address
     * @param string      $local
     * @param string|null $resource
     * @return bool
     */
    public static function validate(string $address, string $local = null, string $resource = null)
    {
        if ($local === null && $resource === null) {
            list($address, $local, $resource) = self::_split($address);
        }

        if (empty($address)) {
            throw new InvalidArgumentException("Domain-part of JID is REQUIRED");
        }

        if (preg_match('#[<>:&"\'/@]#i', $address, $match) !== 0) {
            throw new InvalidArgumentException("Domain-part of JID contains not allowed character '{$match[0]}'");
        }

        if ($local !== null && preg_match('#[<>:&"\'/@]#i', $local, $match) !== 0) {
            throw new InvalidArgumentException("Local-part of JID contains not allowed character '{$match[0]}'");
        }

        if ($resource !== null && preg_match('#[<>:&"\'/]#i', $resource, $match) !== 0) {
            throw new InvalidArgumentException("Resource-part of JID contains not allowed character '{$match[0]}'");
        }

        return true;
    }

    /**
     * Returns if address is valid or not.
     *
     * @param string      $address
     * @param string      $local
     * @param string|null $resource
     * @return bool
     */
    public static function isValid(string $address, string $local = null, string $resource = null) : bool
    {
        try {
            return self::validate($address, $local, $resource);
        } catch (InvalidArgumentException $exception) {
            return false;
        }
    }

    public function __toString() : string
    {
        return
            ($this->_local ? "{$this->_local}@" : null)
            . $this->_domain
            . ($this->_resource ? "/{$this->_resource}" : null);
    }

    /**
     * Returns the domain part of address.
     *
     * @return string
     */
    public function getDomain() : string
    {
        return $this->_domain;
    }

    /**
     * Returns the local part of JID.
     *
     * @return string
     */
    public function getLocal() : string
    {
        return $this->_local;
    }

    /**
     * Returns resource part of address or null if resource is not set
     *
     * @return null|string
     */
    public function getResource()
    {
        return $this->_resource;
    }

    public function bare()
    {
        return new static($this->domain, $this->local, null);
    }

    public function isBare() : bool
    {
        return $this->_resource === null;
    }

    public function isFull() : bool
    {
        return $this->_resource !== null && $this->_local !== null;
    }
}
