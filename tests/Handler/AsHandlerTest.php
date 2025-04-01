<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class AsHandlerTest extends BaseTestCase
{
    public function testApnic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS4837');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('as-block:       AS4608 - AS4865', $data->raw);
        self::assertEquals('whois.apnic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::AS, $data->type);
    }

    public function testArin(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('220');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('ASName:         OOALC-HOSTNET-AS', $data->raw);
        self::assertEquals('whois.arin.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::AS, $data->type);
    }

    public function testRipe(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS3333');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('as-block:       AS3209 - AS3353', $data->raw);
        self::assertEquals('whois.ripe.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::AS, $data->type);
    }

    // todo
    public function testLacnic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS28001');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('???', $data->raw);
        self::assertEquals('whois.lacnic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::AS, $data->type);
    }

    public function testAfrinic(): void
    {
        $handler = new AsHandler($this->createLoggedClient());
        $data = $handler->process('AS33764');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
        self::assertStringContainsString('aut-num:        AS33764', $data->raw);
        self::assertEquals('whois.afrinic.net:43', $data->server);
        self::assertEquals(QueryTypeEnum::AS, $data->type);
    }
}
