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

use Interop\Container\ContainerInterface;
use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Utils\filter;
use Kadet\Xmpp\Utils\helper;

/**
 * Class XmlElement
 * @package Kadet\Xmpp\Xml
 *
 * @property string          $localName  Tag name without prefix
 * @property string          $namespace  XML Namespace URI
 * @property string          $prefix     Tag prefix
 * @property string          $name       Full tag name prefix:local-name
 *
 * @property XmlElement|null $parent     Element's parent or null if root node.
 * @property XmlElement[]    $children   All element's child nodes
 *
 * @property array           $attributes Element's attributes, without xmlns definitions
 * @property array           $namespaces Element's namespaces
 *
 * @property string          $innerXml   Inner XML content
 */
class XmlElement implements ContainerInterface
{
    use Accessors;

    /**
     * Settings for tiding up XML output
     *
     * @var array
     */
    public static $tidy = [
        'indent'           => true,
        'input-xml'        => true,
        'output-xml'       => true,
        'drop-empty-paras' => false,
        'wrap'             => 0
    ];

    /** @var string */
    private $_localName;
    /** @var null|string|false */
    private $_prefix = null;

    /** @var array */
    private $_namespaces = [];
    /** @var array */
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
     * Initializes element with given name and URI
     *
     * @param string $name Element name, including prefix if needed
     * @param string $uri  Namespace URI of element
     */
    protected function init(string $name, string $uri = null)
    {
        list($name, $prefix) = self::resolve($name);

        $this->_localName = $name;
        $this->_prefix    = $prefix;

        if ($uri !== null) {
            $this->namespace = $uri;
        }
    }

    /**
     * XmlElement constructor
     *
     * @param string $name    Element name, including prefix if needed
     * @param string $uri     Namespace URI of element
     * @param mixed  $content Content of element
     */
    public function __construct(string $name, string $uri = null, $content = null)
    {
        $this->init($name, $uri);
        $this->append($content);
    }

    /**
     * Elements named constructor, same for every subclass.
     * It's used for factory creation.
     *
     * @param string $name Element name, including prefix if needed
     * @param string $uri  Namespace URI of element
     *
     * @return static
     */
    public static function plain(string $name, string $uri = null)
    {
        /** @var XmlElement $element */
        $element = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
        $element->init($name, $uri);

        return $element;
    }

    /**
     * @see $innerXml
     * @return string
     */
    public function getInnerXml()
    {
        return implode('', array_map(function ($element) {
            if (is_string($element)) {
                return htmlspecialchars($element);
            } elseif ($element instanceof XmlElement) {
                return $element->xml(false);
            }

            return (string)$element;
        }, $this->_children));
    }

    /**
     * Returns XML representation of element
     *
     * @param bool $clean Result will be cleaned if set to true
     *
     * @return string
     */
    public function xml(bool $clean = true): string
    {
        if ($this->namespace && $this->_prefix === null) {
            $this->_prefix = $this->lookupPrefix($this->namespace);
        }

        $attributes = $this->attributes();

        $result = "<{$this->name}";
        $result .= ' ' . implode(' ', array_map(function ($key, $value) {
            return $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
        }, array_keys($attributes), array_values($attributes)));

        if (!empty($this->_children)) {
            $result .= ">{$this->innerXml}</{$this->name}>";
        } else {
            $result .= "/>";
        }

        return $clean && function_exists('tidy_repair_string') ? tidy_repair_string($result, self::$tidy) : $result;
    }

    /**
     * Looks up prefix associated with given URI
     *
     * @param string|null $uri
     * @return string|false
     */
    public function lookupPrefix(string $uri = null)
    {
        return $this->getNamespaces()[ $uri ] ?? false;
    }

    /**
     * Looks up URI associated with given prefix
     *
     * @param string|null $prefix
     * @return string|false
     */
    public function lookupUri(string $prefix = null)
    {
        return array_search($prefix, $this->getNamespaces()) ?: false;
    }

    /**
     * Returns element's namespaces
     *
     * @param bool $parent Include namespaces from parent?
     * @return array
     */
    public function getNamespaces($parent = true): array
    {
        if (!$this->_parent) {
            return $this->_namespaces;
        }

        if ($parent) {
            return array_merge($this->_namespaces, $this->_parent->getNamespaces());
        } else {
            return array_diff_assoc($this->_namespaces, $this->_parent->getNamespaces());
        }
    }

    /**
     * Sets XML attribute of element
     *
     * @param string      $attribute Attribute name, optionally with prefix
     * @param mixed       $value     Attribute value
     * @param string|null $uri       XML Namespace URI of attribute, prefix will be automatically looked up
     */
    public function setAttribute(string $attribute, $value, string $uri = null)
    {
        $attribute = $this->_prefix($attribute, $uri);
        if ($value === null) {
            unset($this->_attributes[ $attribute ]);

            return;
        }

        $this->_attributes[ $attribute ] = $value;
    }

    /**
     * Returns value of specified attribute.
     *
     * @param string      $attribute Attribute name, optionally with prefix
     * @param string|null $uri       XML Namespace URI of attribute, prefix will be automatically looked up
     * @return bool|mixed
     */
    public function getAttribute(string $attribute, string $uri = null)
    {
        return $this->_attributes[ $this->_prefix($attribute, $uri) ] ?? false;
    }

    /**
     * Checks if attribute exists
     *
     * @param string      $attribute Attribute name, optionally with prefix
     * @param string|null $uri       XML Namespace URI of attribute, prefix will be automatically looked up
     *
     * @return bool
     */
    public function hasAttribute(string $attribute, string $uri = null)
    {
        return isset($this->_attributes[ $this->_prefix($attribute, $uri) ]);
    }

    /**
     * Returns element's parent
     * @return XmlElement|null
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Sets element's parent
     * @param XmlElement $parent
     */
    protected function setParent(XmlElement $parent)
    {
        if (!$this->_prefix && ($prefix = $parent->lookupPrefix($this->namespace)) !== false) {
            $this->_namespaces[ $this->namespace ] = $prefix;
            $this->_prefix                         = $prefix;
        }

        $this->_parent = $parent;
        if ($this->namespace === false) {
            $this->namespace = $parent->namespace;
        }
    }

    /**
     * Appends child to element
     *
     * @param XmlElement|string $element
     *
     * @return XmlElement|string Same as $element
     */
    public function append($element)
    {
        if (empty($element)) {
            return false;
        }

        if (!is_string($element) && !$element instanceof XmlElement) {
            throw new InvalidArgumentException(helper\format('$element should be either string or object of {class} class, {type} given', [
                'class' => XmlElement::class,
                'type'  => helper\typeof($element)
            ]));
        }

        if ($element instanceof XmlElement) {
            $element->parent = $this;
        }

        return $this->_children[] = $element;
    }

    /**
     * Returns namespace URI associated with element
     *
     * @return false|string
     */
    public function getNamespace()
    {
        return $this->lookupUri($this->prefix);
    }

    /**
     * Adds namespace to element, and associates it with prefix.
     *
     * @param string           $uri    Namespace URI
     * @param string|bool|null $prefix Prefix which will be used for namespace, false for using element's prefix
     *                                 and null for no prefix
     */
    public function setNamespace(string $uri, $prefix = false)
    {
        if ($prefix === false) {
            $prefix = $this->_prefix;
        }

        $this->_namespaces[ $uri ] = $prefix;
    }

    public function getName()
    {
        return ($this->_prefix ? $this->prefix . ':' : null) . $this->localName;
    }

    public function getChildren()
    {
        return $this->_children;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function getLocalName()
    {
        return $this->_localName;
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
        return array_values($this->elements($name, $uri))[ $index ] ?? false;
    }

    /**
     * Retrieves array of matching elements
     *
     * @param string      $name Requested element tag name
     * @param string|null $uri  Requested element namespace
     *
     * @return XmlElement[] Found Elements
     */
    public function elements($name, $uri = null) : array
    {
        $predicate = filter\tag($name);
        if ($uri !== null) {
            $predicate = filter\all($predicate, filter\xmlns($uri));
        }

        return $this->all($predicate);
    }

    /**
     * Filters element with given predicate
     *
     * @param callable|string $predicate Predicate or class name
     *
     * @return XmlElement[]
     */
    public function all($predicate)
    {
        return array_values(array_filter($this->_children, filter\predicate($predicate)));
    }

    /**
     * Iterates over matching elements
     *
     * @param callable|string $predicate Predicate or class name
     *
     * @return XmlElement|false
     */
    public function get($predicate)
    {
        $predicate = filter\predicate($predicate);
        foreach ($this->_children as $index => $child) {
            if ($predicate($child)) {
                return $child;
            }
        }

        return false;
    }

    public function has($predicate)
    {
        return $this->get($predicate) !== false;
    }

    /**
     * @param string|null $query
     * @return XPathQuery
     */
    public function query(string $query = null)
    {
        return new XPathQuery($query, $this);
    }

    /**
     * Helper for retrieving all arguments (including namespaces)
     *
     * @return array
     */
    private function attributes(): array
    {
        $namespaces = $this->getNamespaces(false);
        $namespaces = array_map(function ($prefix, $uri) {
            return [$prefix ? "xmlns:{$prefix}" : 'xmlns', $uri];
        }, array_values($namespaces), array_keys($namespaces));

        return array_merge(
            $this->_attributes,
            array_combine(array_column($namespaces, 0), array_column($namespaces, 1))
        );
    }

    /**
     * Prefixes $name with attribute associated with $uri
     *
     * @param string $name Name to prefix
     * @param string $uri  Namespace URI
     *
     * @return string
     */
    protected function _prefix(string $name, string $uri = null): string
    {
        if ($uri === null) {
            return $name;
        }

        if (($prefix = $this->lookupPrefix($uri)) === false) {
            throw new InvalidArgumentException(helper\format('URI "{uri}" is not a registered namespace', ['uri' => $uri]));
        }

        return "{$prefix}:{$name}";
    }

    public function __toString()
    {
        return trim($this->xml(true));
    }

    /**
     * Splits name into local-name and prefix
     *
     * @param $name
     * @return array [$name, $prefix]
     */
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
