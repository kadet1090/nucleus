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

namespace Kadet\Xmpp\Tests;


use Kadet\Xmpp\Utils\DnsResolver;
use phpmock\phpunit\PHPMock;

class DnsResolverTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    public function testResolving()
    {
        $mock = $this->getFunctionMock(substr(DnsResolver::class, 0, strrpos(DnsResolver::class, '\\')), 'dns_get_record');
        $mock->expects($this->exactly(2))->withConsecutive(['foo.tld', DNS_A], ['bar.tld', DNS_SRV])->willReturn([
            [
                'target' => '8.8.8.8',
                'port' => 1337
            ],
            [
                'target' => '8.8.4.4',
                'port' => 2137
            ],
        ], [
            [
                'target' => '10.0.0.1',
                'port' => 1984
            ],
            [
                'target' => 'xmpp.ru',
                'port' => 2033
            ],
        ]);

        $resolver = new DnsResolver([
            'foo.tld' => DNS_A,
            'bar.tld' => DNS_SRV
        ]);

        $this->assertEquals([
            ['8.8.8.8', 1337],
            ['8.8.4.4', 2137],
            ['10.0.0.1', 1984],
            ['xmpp.ru', 2033]
        ], iterator_to_array($resolver));
    }
}
