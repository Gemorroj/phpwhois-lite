<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\Resource\IpServerList;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class IpHandlerTest extends BaseTestCase
{
    #[DataProvider('getIps')]
    public function testFindIpServer(string $query, ?Server $server): void
    {
        $handler = new IpHandler($this->createLoggedClient(), self::createIpServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findIpServer');
        $result = $reflectionMethod->invoke($handler, $query);

        self::assertEquals($server, $result);
    }

    public static function getIps(): \Generator
    {
        yield ['1.1.1.1/8', new Server('https://rdap.apnic.net/', ServerTypeEnum::RDAP)];
        yield ['1.1.1.1', new Server('https://rdap.apnic.net/', ServerTypeEnum::RDAP)];
        yield ['2a00:1450:4011:808::1001', new Server('https://rdap.db.ripe.net/', ServerTypeEnum::RDAP)];
        yield ['2.2.2.2', self::createIpServerList()->serverDefault];
    }

    #[DataProvider('getIpResponse')]
    public function testProcess(string $query, string $expectedString, string $expectedServer): void
    {
        $handler = new IpHandler($this->createLoggedClient(), self::createIpServerList());
        $data = $handler->process($query);
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString($expectedString, $data->getResponseAsString());
        self::assertEquals($expectedServer, $data->server->server);
    }

    public static function getIpResponse(): \Generator
    {
        yield ['127.0.0.1', '"name": "SPECIAL-IPV4-LOOPBACK-IANA-RESERVED"', self::createIpServerList()->serverDefault->server];
        yield ['192.168.0.1', '"name": "PRIVATE-ADDRESS-CBLK-RFC1918-IANA-RESERVED"', self::createIpServerList()->serverDefault->server];
        yield ['192.0.2.0/24', '"handle": "IANA-IP-ARIN"', self::createIpServerList()->serverDefault->server];
        yield ['1.1.1.1', '"handle": "IRT-APNICRANDNET-AU"', 'https://rdap.apnic.net/'];
        yield ['2001:4860:4860::8888', '"handle": "NET6-2001-4860-1"', 'https://rdap.arin.net/registry/'];
        yield ['193.0.11.51', '"parentHandle": "193.0.0.0 - 193.0.23.255"', 'https://rdap.db.ripe.net/'];
        yield ['200.3.13.10', '???', 'https://rdap.lacnic.net/rdap/']; // todo
        yield ['196.216.2.1', '"handle": "196.216.2.0 - 196.216.3.255"', 'https://rdap.afrinic.net/rdap/'];
        yield ['199.71.0.46', 'NetRange:       199.71.0.0 - 199.71.0.255', 'whois.arin.net'];
    }

    private static function createIpServerList(): IpServerList
    {
        $serverList = new IpServerList();
        $serverList->serverDefault = new Server('https://rdap.arin.net/registry', ServerTypeEnum::RDAP);
        $serverList->serversIpv4 = [
            '199.71.0.46' => new Server('whois.arin.net', ServerTypeEnum::WHOIS),
            '1.0.0.0/8' => new Server('https://rdap.apnic.net/', ServerTypeEnum::RDAP),
            '193.0.0.0/8' => new Server('https://rdap.db.ripe.net/', ServerTypeEnum::RDAP),
            '200.0.0.0/8' => new Server('https://rdap.lacnic.net/rdap/', ServerTypeEnum::RDAP),
            '196.0.0.0/8' => new Server('https://rdap.afrinic.net/rdap/', ServerTypeEnum::RDAP),
        ];
        $serverList->serversIpv6 = [
            '2a00::/12' => new Server('https://rdap.db.ripe.net/', ServerTypeEnum::RDAP),
            '2001:4800::/23' => new Server('https://rdap.arin.net/registry/', ServerTypeEnum::RDAP),
        ];

        return $serverList;
    }
}
