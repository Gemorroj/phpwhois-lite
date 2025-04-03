<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class IpHandlerTest extends BaseTestCase
{
    public function testReserved(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('127.0.0.1');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('NetName:        SPECIAL-IPV4-LOOPBACK-IANA-RESERVED', $data->getResponseAsString());
        self::assertEquals('whois.arin.net', $data->server->server); // default server
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testPrivate(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('192.168.0.1');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('NetName:        PRIVATE-ADDRESS-CBLK-RFC1918-IANA-RESERVED', $data->getResponseAsString());
        self::assertEquals('whois.arin.net', $data->server->server); // default server
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testCidr(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('192.0.2.0/24');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('NetType:        IANA Special Use', $data->getResponseAsString());
        self::assertEquals('whois.arin.net', $data->server->server); // default server
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testApnicIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('1.1.1.1');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('inetnum:        1.1.1.0 - 1.1.1.255', $data->getResponseAsString());
        self::assertEquals('whois.apnic.net', $data->server->server);
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testArinIpv6(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('2001:4860:4860::8888');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('CIDR:           2001:4860::/32', $data->getResponseAsString());
        self::assertEquals('whois.arin.net', $data->server->server);
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testRipeIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('193.0.11.51');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('inetnum:        193.0.10.0 - 193.0.11.255', $data->getResponseAsString());
        self::assertEquals('whois.ripe.net', $data->server->server);
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testLacnicIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('200.3.13.10');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('aut-num:     AS28001', $data->getResponseAsString());
        self::assertEquals('whois.lacnic.net', $data->server->server);
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }

    public function testAfrinic(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('196.216.2.1');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('inetnum:        196.216.2.0 - 196.216.3.255', $data->getResponseAsString());
        self::assertEquals('whois.afrinic.net', $data->server->server);
        self::assertEquals(QueryTypeEnum::IP, $data->type);
    }
}
