<?php

namespace WhoRdap\Tests\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use WhoRdap\Handler\AsnHandler;
use WhoRdap\Resource\RdapAsnServerList;
use WhoRdap\Resource\WhoisAsnServerList;
use WhoRdap\Tests\BaseTestCase;

final class AsnHandlerTest extends BaseTestCase
{
    #[DataProvider('getRdapAsns')]
    public function testFindAsnServerRdap(string $query, string $server): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createRdapAsnServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findAsnServer');
        $result = $reflectionMethod->invoke($handler, $query);

        self::assertEquals($server, $result);
    }

    public static function getRdapAsns(): \Generator
    {
        yield ['1', 'https://rdap.arin.net/registry/'];
        yield ['AS1', 'https://rdap.arin.net/registry/'];
        yield ['2043', 'https://rdap.db.ripe.net/'];
        yield ['123123123123', self::createRdapAsnServerList()->serverDefault];
    }

    #[DataProvider('getWhoisAsns')]
    public function testFindAsnServerWhois(string $query, string $server): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createWhoisAsnServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findAsnServer');
        $result = $reflectionMethod->invoke($handler, $query);

        self::assertEquals($server, $result);
    }

    public static function getWhoisAsns(): \Generator
    {
        yield ['1', 'whois.arin.net'];
        yield ['AS1', 'whois.arin.net'];
        yield ['2043', 'whois.ripe.net'];
        yield ['123123123123', self::createWhoisAsnServerList()->serverDefault];
    }

    #[DataProvider('getWhoisAsnResponse')]
    public function testProcessWhois(string $query, string $expectedString, string $expectedServer): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createWhoisAsnServerList());
        $data = $handler->processWhois($query);
        // \file_put_contents('/test.txt', $data->response);
        // var_dump($data->response);
        self::assertStringContainsString($expectedString, $data->response);
        self::assertEquals($expectedServer, $data->server);
    }

    public static function getWhoisAsnResponse(): \Generator
    {
        yield ['AS4837', 'as-block:       AS4608 - AS4865', 'whois.apnic.net'];
        yield ['4837', 'as-block:       AS4608 - AS4865', 'whois.apnic.net'];
        yield ['AS220', 'ASHandle:       AS220', 'whois.arin.net'];
        yield ['220', 'ASHandle:       AS220', 'whois.arin.net'];
        yield ['AS2043', 'as-block:       AS2043 - AS2043', 'whois.ripe.net'];
        yield ['2043', 'as-block:       AS2043 - AS2043', 'whois.ripe.net'];
        yield ['AS28001', 'aut-num:     AS28001', 'whois.lacnic.net'];
        yield ['28001', 'aut-num:     AS28001', 'whois.lacnic.net'];
        yield ['AS36864', 'aut-num:        AS36864', 'whois.afrinic.net'];
        yield ['36864', 'aut-num:        AS36864', 'whois.afrinic.net'];
    }

    #[DataProvider('getRdapAsnResponse')]
    public function testProcessRdap(string $query, string $expectedString, string $expectedServer): void
    {
        $handler = new AsnHandler($this->createLoggedClient(), self::createRdapAsnServerList());
        $data = $handler->processRdap($query);
        // \file_put_contents('/test.txt', $data->response);
        // var_dump($data->response);
        self::assertStringContainsString($expectedString, $data->response);
        self::assertEquals($expectedServer, $data->server);
    }

    public static function getRdapAsnResponse(): \Generator
    {
        yield ['AS4837', '"handle":"AS4837"', 'https://rdap.apnic.net/'];
        yield ['4837', '"handle":"AS4837"', 'https://rdap.apnic.net/'];
        yield ['AS220', '"handle" : "AS220"', 'https://rdap.arin.net/registry/'];
        yield ['220', '"handle" : "AS220"', 'https://rdap.arin.net/registry/'];
        yield ['AS2043', '"handle" : "KPN-RIPE"', 'https://rdap.db.ripe.net/'];
        yield ['2043', '"handle" : "KPN-RIPE"', 'https://rdap.db.ripe.net/'];
        yield ['AS28001', '"handle":"AS28001"', 'https://rdap.lacnic.net/rdap/'];
        yield ['28001', '"handle":"AS28001"', 'https://rdap.lacnic.net/rdap/'];
        yield ['AS36864', '"handle":"AS36864"', 'https://rdap.afrinic.net/rdap/'];
        yield ['36864', '"handle":"AS36864"', 'https://rdap.afrinic.net/rdap/'];
    }

    private static function createRdapAsnServerList(): RdapAsnServerList
    {
        $serverList = new RdapAsnServerList();
        $serverList->serverDefault = 'https://rdap.arin.net/registry';
        $serverList->servers = [
            '1-1876' => 'https://rdap.arin.net/registry/',
            '2043' => 'https://rdap.db.ripe.net/',
            '4608-4865' => 'https://rdap.apnic.net/',
            '27648-28671' => 'https://rdap.lacnic.net/rdap/',
            '36864-37887' => 'https://rdap.afrinic.net/rdap/',
        ];

        return $serverList;
    }

    private static function createWhoisAsnServerList(): WhoisAsnServerList
    {
        $serverList = new WhoisAsnServerList();
        $serverList->serverDefault = 'whois.arin.net';
        $serverList->servers = [
            '1-1876' => 'whois.arin.net',
            '2043' => 'whois.ripe.net',
            '4608-4865' => 'whois.apnic.net',
            '27648-28671' => 'whois.lacnic.net',
            '36864-37887' => 'whois.afrinic.net',
        ];

        return $serverList;
    }
}
