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

namespace Kadet\Xmpp;


use DI\Container;
use DI\ContainerBuilder;
use Interop\Container\ContainerInterface;
use Kadet\Xmpp\Exception\InvalidArgumentException;
use Kadet\Xmpp\Module\Authentication;
use Kadet\Xmpp\Network\Connector;
use Kadet\Xmpp\Utils\Accessors;
use Kadet\Xmpp\Utils\filter as with;
use Kadet\Xmpp\Utils\ServiceManager;
use Kadet\Xmpp\Xml\XmlElementFactory;
use Kadet\Xmpp\Xml\XmlParser;
use React\EventLoop\LoopInterface;

/**
 * Class XmppClient
 * @package Kadet\Xmpp
 *
 * @property-read Jid $jid Client's jid (Jabber Identifier) address.
 */
class XmppClient extends XmppStream implements ContainerInterface
{
    use ServiceManager, Accessors;

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
     * XmppClient constructor.
     * @param Jid                     $jid
     * @param string                  $password
     * @param Connector|LoopInterface $connector
     * @param XmlParser|null          $parser
     * @param string                  $lang
     */
    public function __construct(Jid $jid, string $password, $connector = null, XmlParser $parser = null, $lang = 'en')
    {
        parent::__construct(
            $parser ?: new XmlParser(new XmlElementFactory()),
            null, // will be set by event
            $lang
        );

        $this->_container = ContainerBuilder::buildDevContainer();

        $this->_jid      = $jid;
        $this->register(new Authentication($password));

        $this->setConnector($connector);
        $this->connect();

        $this->_connector->on('connect', function(...$arguments) {
            return $this->emit('connect', $arguments);
        });
    }

    public function connect()
    {
        $this->getLogger()->debug("Connecting to {$this->_jid->domain}");

        $this->_connector->connect();
    }

    public function getJid()
    {
        return $this->_jid;
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
                '$connector must be either %s, or %s instance %s given.',
                LoopInterface::class, Connector::class, \Kadet\Xmpp\Utils\helper\typeof($connector)
            ));
        }

        $this->_connector->on('connect', function($stream) {
            $this->handleConnect($stream);
        });
    }

    protected function register(XmppModule $module, $as = null)
    {
        $module->setClient($this);
        $this->_container->set($as ?: get_class($module), $module);
    }

    protected function getContainer() : ContainerInterface
    {
        return $this->_container;
    }
}
