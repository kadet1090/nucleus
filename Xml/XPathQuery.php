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

namespace Kadet\Xmpp\Xml;


class XPathQuery
{
    private $query;
    /** @var \DOMXPath */
    private $xpath;
    private $context;

    public function with(string $prefix, string $namespace)
    {
        $this->xpath->registerNamespace($prefix, $namespace);

        return $this;
    }

    public function query(string $query = null)
    {
        return $this->xpath->query($query ?: $this->query, $this->context);
    }

    public function evaluate(string $query = null)
    {
        return $this->xpath->evaluate($query ?: $this->query, $this->context);
    }

    public function __construct(\DOMDocument $document, string $query, \DOMNode $context)
    {
        $this->xpath   = new \DOMXPath($document);
        $this->with('php', 'http://php.net/xpath');

        $this->query   = $query;
        $this->context = $context;
    }
}
