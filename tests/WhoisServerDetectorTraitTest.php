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
}
