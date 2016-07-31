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

use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Utils\Accessors;

use \Kadet\Xmpp\Utils\helper;
use \Kadet\Xmpp\Utils\filter;

/**
 * Class XmlElement
 * @package Kadet\Xmpp\Xml
 *
 * @property string $localName Tag name without prefix.
 * @property string $namespace
 * @property string $prefix
 * @property string $name
 * @property string $innerXml
 * @property XmlElement $parent
 * @property XmlElement[] $children
 * @property array $attributes
 * @property array $namespaces
 */
class XmlElement
{
    use Accessors;

    public static $tidy = [
        'indent'           => true,
        'input-xml'        => true,
        'output-xml'       => true,
        'drop-empty-paras' => false,
        'wrap'             => 0
    ];

    private $_localName;
    private $_prefix = null;

    private $_namespaces = [
    ];
    private $_attributes = [];

    /**
     * @var XmlElement
     */
    private $_parent;

    /**
     * @var XmlElement[]
     */
    private $_children = [];

    /**
     * XmlElement constructor.
     * @param string $name
     * @param string $uri
     */
    public function __construct(string $name, string $uri = null)
    {
        list($name, $prefix) = self::resolve($name);

        $this->_localName = $name;
        $this->_prefix    = $prefix;

        if ($uri !== null) {
            $this->namespace = $uri;
        }
    }

    public function __toString()
    {
        return trim($this->xml(true));
    }

    public function xml($clean = true): string
    {
        if($this->namespace && $this->_prefix === null) {
            $this->_prefix = $this->lookupPrefix($this->namespace);
        }

        $attributes = $this->attributes();

        $result = "<{$this->name}";
        $result .= ' '.implode(' ', array_map(function($key, $value) {
            return $key.'="'.htmlspecialchars($value, ENT_QUOTES).'"';
        }, array_keys($attributes), array_values($attributes)));

        if(!empty($this->_children)) {
            $result .= ">{$this->innerXml}</{$this->name}>";
        } else {
            $result .= "/>";
        }

        return $clean && function_exists('tidy_repair_string') ? tidy_repair_string($result, self::$tidy) : $result;
    }

    public function getInnerXml()
    {
        return implode('', array_map(function($element) {
            if(is_string($element)) {
                return htmlspecialchars($element);
            } elseif ($element instanceof XmlElement) {
                return $element->xml(false);
            }

            return (string)$element;
        }, $this->_children));
    }

    public function setAttribute(string $attribute, $value, string $uri = null)
    {
        if($uri === 'http://www.w3.org/2000/xmlns/') {
            $this->setNamespace($value, $attribute);
            return;
        }

        if($uri !== null) {
            $attribute = $this->_prefix($attribute, $uri);
        }

        $this->_attributes[$attribute] = $value;
    }

    public function getAttribute(string $attribute, string $uri = null)
    {
        if($uri !== null) {
            $attribute = $this->_prefix($attribute, $uri);
        }

        return $this->_attributes[$attribute] ?? false;
    }

    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * @return array
     */
    public function getNamespaces($parent = true): array
    {
        if(!$this->_parent) {
            return $this->_namespaces;
        }

        if($parent) {
            return array_merge($this->_namespaces, $this->_parent->getNamespaces());
        } else {
            return array_diff_assoc($this->_namespaces, $this->_parent->getNamespaces());
        }
    }

    private function attributes(): array
    {
        $namespaces = $this->getNamespaces(false);
        $namespaces = array_map(function($prefix, $uri) {
            return [ $prefix ? "xmlns:{$prefix}" : 'xmlns', $uri ];
        }, array_values($namespaces), array_keys($namespaces));

        return array_merge(
            $this->_attributes,
            array_combine(array_column($namespaces, 0), array_column($namespaces, 1))
        );
    }

    public function lookupUri(string $prefix = null)
    {
        return array_search($prefix, $this->getNamespaces()) ?: false;
    }

    public function lookupPrefix(string $uri = null)
    {
        return $this->getNamespaces()[$uri] ?? false;
    }

    /**
     * @param string       $uri
     * @param string|false $prefix
     */
    public function setNamespace(string $uri, $prefix = false)
    {
        if($prefix === false) {
            $prefix = $this->_prefix;
        }

        $this->_namespaces[$uri] = $prefix;
    }

    public function getNamespace()
    {
        return $this->lookupUri($this->prefix);
    }

    public function getChildren()
    {
        return $this->_children;
    }

    public function append($element)
    {
        if(!is_string($element) && !$element instanceof XmlElement) {
            throw new InvalidArgumentException(helper\format('$element should be either string or object of {class} class, {type} given', [
                'class' => XmlElement::class,
                'type'  => helper\typeof($element)
            ]));
        }

        if($element instanceof XmlElement) {
            $element->parent  = $this;
        }

        return $this->_children[] = $element;
    }

    public function getName()
    {
        return ($this->_prefix ? $this->prefix.':' : null).$this->localName;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function getLocalName()
    {
        return $this->_localName;
    }

    protected function setParent(XmlElement $parent)
    {
        if(!$this->_prefix && ($prefix = $parent->lookupPrefix($this->namespace)) !== false) {
            $this->_namespaces[$this->namespace] = $prefix;
            $this->_prefix = $prefix;
        }

        $this->_parent = $parent;
        if($this->namespace === false) {
            $this->namespace = $parent->namespace;
        }

        if(!$this->_prefix) {
            $this->_prefix = $this->lookupPrefix($this->namespace);
        }
    }

    /**
     * Retrieves array of matching elements
     *
     * @param string $name  Requested element tag name
     * @param null   $uri   Requested element namespace
     *
     * @return XmlElement[] Found Elements
     */
    public function elements($name, $uri = null) : array
    {
        $predicate = filter\tag($name);
        if($uri !== null) {
            $predicate = filter\all($predicate, filter\xmlns($uri));
        }

        return $this->all($predicate);
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Returns one element at specified index (for default the first one).
     *
     * @param string $name  Requested element tag name
     * @param string $uri   Requested element namespace
     * @param int    $index Index of element to retrieve
     *
     * @return XmlElement|false Retrieved element
     */
    public function element(string $name, string $uri = null, int $index = 0)
    {
        return array_values($this->elements($name, $uri))[$index] ?? false;
    }

    public function all($predicate) {
        $predicate = filter\predicate($predicate);
        return array_filter($this->_children, $predicate);
    }

    /**
     * @param string|null $query
     * @return XPathQuery
     */
    public function query(string $query = null)
    {
        return new XPathQuery($query, $this);
    }

    protected function init() { }

    public static function plain(string $name, string $uri = null)
    {
        $reflection = new \ReflectionClass(static::class);

        list($name, $prefix) = static::resolve($name);

        /** @var XmlElement $element */
        $element = $reflection->newInstanceWithoutConstructor();
        $element->_localName = $name;
        $element->_prefix    = $prefix;

        if ($uri !== null) {
            $element->namespace = $uri;
        }

        $element->init();
        return $element;
    }

    /**
     * @param string $name
     * @param string $uri
     * @return string
     */
    protected function _prefix(string $name, string $uri): string
    {
        if (($prefix = $this->lookupPrefix($uri)) === false) {
            throw new InvalidArgumentException(helper\format('URI "{uri}" is not a registered namespace', ['uri' => $uri]));
        }

        return "{$prefix}:{$name}";
    }

    public static function resolve($name)
    {
        $prefix = null;
        if (($pos = strpos($name, ':')) !== false) {
            $prefix = substr($name, 0, $pos);
            $name   = substr($name, $pos + 1);
        }

        return [$name, $prefix];
    }
}
