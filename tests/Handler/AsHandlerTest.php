<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Tests\BaseTestCase;

final class AsHandlerTest extends BaseTestCase
{
    public function testApnic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS4837');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('as-block:       AS4608 - AS4865', $data->getResponseAsString());
        self::assertEquals('whois.apnic.net', $data->server->server);
    }

    public function testArin(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('220');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('ASName:         OOALC-HOSTNET-AS', $data->getResponseAsString());
        self::assertEquals('whois.arin.net', $data->server->server);
    }

    public function testRipe(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS3333');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('as-block:       AS3209 - AS3353', $data->getResponseAsString());
        self::assertEquals('whois.ripe.net', $data->server->server);
    }

    public function testLacnic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS28001');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('responsible: Ernesto Majó', $data->getResponseAsString());
        self::assertEquals('whois.lacnic.net', $data->server->server);
    }

    public function testAfrinic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS33764');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('aut-num:        AS33764', $data->getResponseAsString());
        self::assertEquals('whois.afrinic.net', $data->server->server);
    }
}
