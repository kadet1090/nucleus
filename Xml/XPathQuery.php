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
        /** @var \DOMNode $element */
        foreach($this->_xpath->query($query ?: $this->_query) as $element) {
            yield $this->getElementFromPath($element->getNodePath());
        }
    }

    public function evaluate(string $query = null)
    {
        return $this->_xpath->evaluate($query ?: $this->_query);
    }

    public function __construct(string $query, XmlElement $context)
    {
        $document = new \DOMDocument();
        $document->loadXML($context->xml(false));
        $this->_xpath   = new \DOMXPath($document);
        $this->with('php', 'http://php.net/xpath');

        $this->_query   = $query;
        $this->_context = $context;
    }

    /**
     * Hack for supporting XPath outside of standard XML implementation.
     * Why? Becuase simplexml sucks so much.
     * DOM suck even more, and I'm better with writing one hack for xpath
     * than with writing hack for almost everyfuckingthing™.
     *
     * @param $path
     * @return false|XmlElement
     */
    private function getElementFromPath($path)
    {
        // Split path into pieces and remove the first one (it's current node)
        $path = explode('/', trim($path, '/'));
        array_shift($path);

        $current = $this->_context;
        foreach($path as $chunk) {
            // Chunk is in format node-name[index], parse it with regex
            preg_match('/([\w\*]+)(?:\[([0-9]+)\])?/', $chunk, $matches);

            $name  = $matches[1];
            $index = isset($matches[2]) ? $matches[2] - 1 : 0;

            if($name == '*') {
                // Path returns * if namespace occurs so we need to obtain index-th child
                $current = $current->children[$index];
            } else {
                // We need to obtain index-th child with $name name
                $current = $current->element($name, null, $index);
            }
        }

        return $current;
    }
}
