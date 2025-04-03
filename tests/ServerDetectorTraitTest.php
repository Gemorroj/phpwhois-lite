<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\RdapResponse;
use PHPWhoisLite\Response\WhoisResponse;
use PHPWhoisLite\ServerDetectorTrait;

final class ServerDetectorTraitTest extends BaseTestCase
{
    use ServerDetectorTrait;

    #[DataProvider('getBaseServers')]
    public function testFindBaseServer(RdapResponse|WhoisResponse $response, ?Server $expectedServer): void
    {
        $result = $this->findBaseServer($response);
        self::assertEquals($expectedServer, $result);
    }

    public static function getBaseServers(): \Generator
    {
        yield [new WhoisResponse('test African Network Information Center test'), new Server('whois.afrinic.net', ServerTypeEnum::WHOIS)];
        yield [new WhoisResponse(' not found string '), null];
    }

    #[DataProvider('getDomains')]
    public function testFindServer(string $query, ?Server $server): void
    {
        $serverList = $this->createServerList();
        $result = $this->findServer($serverList, $query);
        self::assertEquals($server, $result);
    }

    public static function getDomains(): \Generator
    {
        yield ['vk.com', new Server('whois.verisign-grs.com', ServerTypeEnum::WHOIS)];
        yield ['ya.ru', new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS)];
        yield ['test.org.ru', new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield ['test.tjmaxx', new Server('https://rdap.nic.tjmaxx/', ServerTypeEnum::RDAP)];
        yield ['domain.unknowntld', (new ServerList())->serverDefault];
    }

    #[DataProvider('getRegistrarData')]
    public function testFindRegistrarServer(RdapResponse|WhoisResponse $response, ?Server $expectedServer): void
    {
        $result = $this->findRegistrarServer($response);
        self::assertEquals($expectedServer, $result);
    }

    public static function getRegistrarData(): \Generator
    {
        yield [new WhoisResponse('   Registrar WHOIS Server: whois.nic.ru'), new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield [new WhoisResponse('   Registrar WHOIS Server: rwhois://whois.nic.ru'), new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield [new WhoisResponse('   test: string'), null];
        yield [new WhoisResponse('   Registrar WHOIS Server: file://passwd.com'), null];
        yield [new WhoisResponse('   Registrar WHOIS Server: '), null];
        yield [new WhoisResponse('whois:        whois.tcinet.ru '), null]; // the pattern from whois.iana.org
        yield [new WhoisResponse('whois:         '), null];
        yield [new WhoisResponse(' whois:'), null];
    }

    #[DataProvider('getWhoisServers')]
    public function testPrepareWhoisServer(string $server, ?Server $preparedServer): void
    {
        try {
            $result = $this->prepareWhoisServer($server);
        } catch (\Exception) {
            $result = null;
        }
        self::assertEquals($preparedServer, $result);
    }

    public static function getWhoisServers(): \Generator
    {
        yield ['localhost', new Server('localhost', ServerTypeEnum::WHOIS)];
        yield ['whois.nic.ru', new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield ['rwhois://whois.nic.ru', new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield ['whois://whois.nic.ru', new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield ['whois.nic.ru:44', new Server('whois.nic.ru:44', ServerTypeEnum::WHOIS)];
        yield ['http://test.com/?123&456', null]; // ignore http servers
        yield ['https://test.com/?123&456', null]; // ignore http servers
        yield ['file://passwords', null];
        yield ['/passwords', null];
        yield ['\\passwords', null];
    }
}
