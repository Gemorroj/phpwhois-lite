<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class IpHandlerTest extends BaseTestCase
{
    public function testApnicIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('1.1.1.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('inetnum:        1.1.1.0 - 1.1.1.255', $data->raw);
        self::assertEquals('whois.apnic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::IPv4, $data->type);
    }

    public function testArinIpv6(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('2001:4860:4860::8888');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('CIDR:           2001:4860::/32', $data->raw);
        self::assertEquals('whois.arin.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::IPv6, $data->type);
    }

    public function testRipeIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('193.0.11.51');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('inetnum:        193.0.10.0 - 193.0.11.255', $data->raw);
        self::assertEquals('whois.ripe.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::IPv4, $data->type);
    }

    public function testLacnicIpv4(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('200.3.13.10');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('aut-num:     AS28001', $data->raw);
        self::assertEquals('whois.lacnic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::IPv4, $data->type);
    }

    public function testAfrinic(): void
    {
        $handler = new IpHandler($this->createLoggedClient());
        $data = $handler->process('196.216.2.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('inetnum:        196.216.2.0 - 196.216.3.255', $data->raw);
        self::assertEquals('whois.afrinic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::IPv4, $data->type);
    }
}
