<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Resource\WhoisServerList;
use PHPWhoisLite\WhoisServerDetectorTrait;

final class WhoisServerDetectorTraitTest extends BaseTestCase
{
    use WhoisServerDetectorTrait;

    public function testFindBaseServer(): void
    {
        $str = 'test African Network Information Center test';
        $result = $this->findBaseServer($str);
        self::assertEquals('whois.afrinic.net:43', $result);
    }

    #[DataProvider('getDomains')]
    public function testFindServer(string $query, string $server): void
    {
        $whoisServerList = new WhoisServerList();
        $result = $this->findServer($whoisServerList, $query);
        self::assertEquals($server, $result);
    }

    public static function getDomains(): \Generator
    {
        yield ['vk.com', 'whois.crsnic.net:43'];
        yield ['ya.ru', 'whois.ripn.net:43'];
        yield ['test.org.ru', 'whois.nic.ru:43'];
    }

    #[DataProvider('getRegistrarData')]
    public function testFindRegistrarServer(string $raw, ?string $server): void
    {
        $result = $this->findRegistrarServer($raw);
        self::assertEquals($server, $result);
    }

    public static function getRegistrarData(): \Generator
    {
        yield ['   Registrar WHOIS Server: whois.nic.ru', 'whois.nic.ru:43'];
        yield ['   Registrar WHOIS Server: rwhois://whois.nic.ru', 'whois.nic.ru:43'];
        yield ['   test: string', null];
        yield ['   Registrar WHOIS Server: file://passwd.com', null];
        yield ['   Registrar WHOIS Server: ', null];
    }

    #[DataProvider('getServers')]
    public function testPrepareWhoisServer(string $server, ?string $preparedServer): void
    {
        try {
            $result = $this->prepareWhoisServer($server);
        } catch (\Exception) {
            $result = null;
        }
        self::assertEquals($preparedServer, $result);
    }

    public static function getServers(): \Generator
    {
        yield ['localhost', 'localhost:43'];
        yield ['whois.nic.ru', 'whois.nic.ru:43'];
        yield ['rwhois://whois.nic.ru', 'whois.nic.ru:43'];
        yield ['whois://whois.nic.ru', 'whois.nic.ru:43'];
        yield ['whois.nic.ru:44', 'whois.nic.ru:44'];
        yield ['http://test.com/?123&456', 'http://test.com/?123&456'];
        yield ['https://test.com/?123&456', 'https://test.com/?123&456'];
        yield ['file://passwords', null];
        yield ['/passwords', null];
        yield ['\\passwords', null];
    }
}
