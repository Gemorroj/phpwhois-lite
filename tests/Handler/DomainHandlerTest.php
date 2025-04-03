<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class DomainHandlerTest extends BaseTestCase
{
    public function testRu(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('ru');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('["org",[],"text","Coordination Center for TLD RU"]', $data->getResponseAsString());
        self::assertEquals('https://rdap.iana.org', $data->server->server); // hardcoded server for root tld
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    public function testLocalhost(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('localhost');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('"errorCode":404,"title":"Not Found","description":["Domain not found :","localhost"]', $data->getResponseAsString());
        self::assertEquals('https://rdap.iana.org', $data->server->server); // hardcoded server for root tld
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    public function testVkCom(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('vk.com');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('Registrar: Regional Network Information Center, JSC dba RU-CENTER', $data->getResponseAsString());
        self::assertEquals('whois.nic.ru', $data->server->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // non UTF-8 server
    public function testRegistroBr(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('registro.br');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('Núcleo de Inf. e Coord. do Ponto BR - NIC.BR', $data->getResponseAsString());
        self::assertEquals('whois.registro.br', $data->server->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // rdap server
    public function testNicTjmaxx(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('nic.tjmaxx');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('"events":[{"eventAction":"registration","eventDate":"2013-08-09T22:59:03Z","eventActor":"The TJX Companies, Inc."}', $data->getResponseAsString());
        self::assertEquals('https://rdap.nic.tjmaxx/', $data->server->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // punycode
    public function testPrzidentRf(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('президент.рф');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('org:           Special Communications and Information Service of the Federal Guard Service of the Russian Federation (Spetssvyaz FSO RF)', $data->getResponseAsString());
        self::assertEquals('whois.tcinet.ru', $data->server->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // force whois server
    public function testSirusSu(): void
    {
        $serverList = $this->createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('sirus.su', new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS));
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
        self::assertStringContainsString('e-mail:        sir.nyll@gmail.com', $data->getResponseAsString());
        self::assertEquals('whois.tcinet.ru', $data->server->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }
}
