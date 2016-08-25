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

namespace Kadet\Xmpp;

use DI\Container;
use DI\ContainerBuilder;
use Interop\Container\ContainerInterface;
use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Module\Authenticator;
use Kadet\Xmpp\Module\Binding;
use Kadet\Xmpp\Module\ClientModule;
use Kadet\Xmpp\Module\ClientModuleInterface;
use Kadet\Xmpp\Module\SaslAuthenticator;
use Kadet\Xmpp\Module\TlsEnabler;
use Kadet\Xmpp\Network\Connector;
use Kadet\Xmpp\Stream\Features;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\Utils\ServiceManager;
use Kadet\Xmpp\Xml\XmlElementFactory;
use Kadet\Xmpp\Xml\XmlParser;
use Kadet\Xmpp\Xml\XmlStream;
use React\EventLoop\LoopInterface;

/**
 * Class XmppClient
 * @package Kadet\Xmpp
 *
 * @property-read Jid                $jid       Client's jid (Jabber Identifier) address.
 * @property-read Features           $features  Features provided by that stream
 * @property-read ContainerInterface $container Dependency container used for module management.
 * @property-read string             $language  Stream language (reflects xml:language attribute)
 *
 * @property-write Connector         $connector
 */
class XmppClient extends XmlStream implements ContainerInterface
{
    use ServiceManager, Accessors;

    private $_attributes = [];
    private $_lang;

    /**
     * Connector used to instantiate stream connection to server.
     *
     * @var Connector
     */
    protected $_connector;

    /**
     * Client's jid (Jabber Identifier) address.
     *
     * @var Jid
     */
    protected $_jid;

    /**
     * Dependency container used as service manager.
     *
     * @var Container
     */
    protected $_container;

    /**
     * Features provided by that stream
     *
     * @var Features
     */
    protected $_features;

    /**
     * XmppClient constructor.
     * @param Jid              $jid
     * @param array            $options {
     *     @var XmlParser          $parser          Parser used for interpreting streams.
     *     @var ClientModule[]     $modules         Additional modules registered when creating client.
     *     @var string             $language        Stream language (reflects xml:language attribute)
     *     @var ContainerInterface $container       Dependency container used for module management.
     *     @var bool               $default-modules Load default modules or not
     * }
     */
    public function __construct(Jid $jid, array $options = [])
    {
        $options = array_replace([
            'parser'    => new XmlParser(new XmlElementFactory()),
            'language'  => 'en',
            'container' => ContainerBuilder::buildDevContainer(),
            'connector' => $options['connector'] ?? new Connector\TcpXmppConnector($jid->domain, $options['loop']),

            'modules'         => [],
            'default-modules' => true,
        ], $options);
        $options['jid'] = $jid;

        parent::__construct($options['parser'], null);
        $this->_parser->factory->load(require __DIR__ . '/XmlElementLookup.php');

        $this->applyOptions($options);

        $this->_connector->on('connect', function (...$arguments) {
            return $this->emit('connect', $arguments);
        });

        $this->on('element', function (Features $element) {
            $this->_features = $element;
            $this->emit('features', [$element]);
        }, Features::class);

        $this->connect();
    }

    public function applyOptions(array $options)
    {
        unset($options['parser']); // don't need that
        $options = \Kadet\Xmpp\Utils\helper\rearrange($options, [
            'container' => 6,
            'jid'       => 5,
            'connector' => 4,
            'modules'   => 3,
            'password'  => -1
        ]);

        if($options['default-modules']) {
            $options['modules'] = array_merge([
                TlsEnabler::class    => new TlsEnabler(),
                Binding::class       => new Binding(),
                Authenticator::class => new SaslAuthenticator()
            ], $options['modules']);
        }

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }
    }

    public function start(array $attributes = [])
    {
        $this->_attributes = $attributes;

        parent::start(array_merge([
            'xmlns'    => 'jabber:client',
            'version'  => '1.0',
            'xml:lang' => $this->_lang
        ], $attributes));
    }

    public function restart()
    {
        $this->getLogger()->debug('Restarting stream', $this->_attributes);
        $this->start($this->_attributes);
    }

    public function connect()
    {
        $this->getLogger()->debug("Connecting to {$this->_jid->domain}");

        $this->_connector->connect();
    }

    public function setResource(string $resource)
    {
        $this->_jid = new Jid($this->_jid->domain, $this->_jid->local, $resource);
    }

    public function getJid()
    {
        return $this->_jid;
    }

    protected function setJid(Jid $jid)
    {
        $this->_jid = $jid;
    }

    public function bind($jid)
    {
        $this->jid = new Jid($jid);
        $this->emit('bind', [$jid]);

        $this->emit('ready', []);
    }

    private function handleConnect($stream)
    {
        $this->exchangeStream($stream);

        $this->getLogger()->info("Connected to {$this->_jid->domain}");
        $this->start([
            'from' => (string)$this->_jid,
            'to'   => $this->_jid->domain
        ]);
    }

    /**
     * @param $connector
     */
    protected function setConnector($connector)
    {
        if ($connector instanceof LoopInterface) {
            $this->_connector = new Connector\TcpXmppConnector($this->_jid->domain, $connector);
        } elseif ($connector instanceof Connector) {
            $this->_connector = $connector;
        } else {
            throw new InvalidArgumentException(sprintf(
                '$connector must be either %s or %s instance, %s given.',
                LoopInterface::class, Connector::class, \Kadet\Xmpp\Utils\helper\typeof($connector)
            ));
        }

        $this->_connector->on('connect', function ($stream) {
            $this->handleConnect($stream);
        });
    }

    public function register(ClientModuleInterface $module, $alias = true)
    {
        $module->setClient($this);
        if ($alias === true) {
            $this->_container->set(get_class($module), $module);
            $aliases = array_merge(class_implements($module), array_slice(class_parents($module), 1));
            foreach ($aliases as $alias) {
                if (!$this->has($alias)) {
                    $this->_container->set($alias, $module);
                }
            }
        } else {
            $this->_container->set($alias === true ? get_class($module) : $alias, $module);
        }
    }

    protected function getContainer() : ContainerInterface
    {
        return $this->_container;
    }

    protected function setContainer(Container $container)
    {
        $this->_container = $container;
    }

    public function getLanguage(): string
    {
        return $this->_lang;
    }

    public function setLanguage(string $language)
    {
        $this->_lang = $language;
    }

    public function setPassword(string $password)
    {
        $this->get(Authenticator::class)->setPassword($password);
    }

    public function setModules(array $modules) {
        foreach ($modules as $name => $module) {
            $this->register($module, is_string($name) ? $name : true);
        }
    }

    public function getFeatures()
    {
        return $this->_features;
    }
}
