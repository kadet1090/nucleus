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
    private $_query;
    /** @var \DOMXPath */
    private $_xpath;
    private $_context;

    public function with(string $prefix, string $namespace)
    {
        $this->_xpath->registerNamespace($prefix, $namespace);

        return $this;
    }

    public function query(string $query = null)
    {
        return $this->_xpath->query($query ?: $this->_query, $this->_context);
    }

    public function evaluate(string $query = null)
    {
        return $this->_xpath->evaluate($query ?: $this->_query, $this->_context);
    }

    public function __construct(\DOMDocument $document, string $query, \DOMNode $context)
    {
        $this->_xpath   = new \DOMXPath($document);
        $this->with('php', 'http://php.net/xpath');

        $this->_query   = $query;
        $this->_context = $context;
    }
}
