<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Handler\AsnHandler;
use PHPWhoisLite\Resource\AsnServerList;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class AsnHandlerTest extends BaseTestCase
{
    #[DataProvider('getAsns')]
    public function testFindAsnServer(string $query, ?Server $server): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createAsnServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findAsnServer');
        $result = $reflectionMethod->invoke($handler, $query);

        self::assertEquals($server, $result);
    }

    public static function getAsns(): \Generator
    {
        yield ['1', new Server('https://rdap.arin.net/registry/', ServerTypeEnum::RDAP)];
        yield ['AS1', new Server('https://rdap.arin.net/registry/', ServerTypeEnum::RDAP)];
        yield ['2043', new Server('https://rdap.db.ripe.net/', ServerTypeEnum::RDAP)];
        yield ['3333', new Server('whois.arin.net', ServerTypeEnum::WHOIS)];
        yield ['123123123123', self::createAsnServerList()->serverDefault];
    }

    #[DataProvider('getAsnResponse')]
    public function testProcess(string $query, string $expectedString, string $expectedServer): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createAsnServerList());
        $data = $handler->process($query);
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString($expectedString, $data->getResponseAsString());
        self::assertEquals($expectedServer, $data->server->server);
    }

    public static function getAsnResponse(): \Generator
    {
        yield ['AS4837', '"handle": "AS4837"', 'https://rdap.apnic.net/'];
        yield ['220', '"handle": "AS220"', 'https://rdap.arin.net/registry/'];
        yield ['AS2043', '"handle": "KPN-RIPE"', 'https://rdap.db.ripe.net/'];
        yield ['AS28001', '"handle": "UY-LACN-LACNIC"', 'https://rdap.lacnic.net/rdap/'];
        yield ['36864', '"handle": "AS36864"', 'https://rdap.afrinic.net/rdap/'];
        yield ['3333', 'ASNumber:       3154 - 3353', 'whois.arin.net'];
    }

    private static function createAsnServerList(): AsnServerList
    {
        $serverList = new AsnServerList();
        $serverList->serverDefault = new Server('https://rdap.arin.net/registry', ServerTypeEnum::RDAP);
        $serverList->servers = [
            '3333' => new Server('whois.arin.net', ServerTypeEnum::WHOIS),
            '1-1876' => new Server('https://rdap.arin.net/registry/', ServerTypeEnum::RDAP),
            '2043' => new Server('https://rdap.db.ripe.net/', ServerTypeEnum::RDAP),
            '4608-4865' => new Server('https://rdap.apnic.net/', ServerTypeEnum::RDAP),
            '27648-28671' => new Server('https://rdap.lacnic.net/rdap/', ServerTypeEnum::RDAP),
            '36864-37887' => new Server('https://rdap.afrinic.net/rdap/', ServerTypeEnum::RDAP),
        ];

        return $serverList;
    }
}
