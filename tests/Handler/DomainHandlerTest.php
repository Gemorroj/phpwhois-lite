<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Tests\BaseTestCase;

final class DomainHandlerTest extends BaseTestCase
{
    public function testRegistrarServerException(): void
    {
        $serverList = new ServerList();
        $serverList->serverDefault = new Server('https://rdap.iana.org', ServerTypeEnum::RDAP);
        $serverList->servers = [
            '.com' => new Server('https://rdap.verisign.com/com/v1/', ServerTypeEnum::RDAP),
        ];

        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('vk.com'); // https://www.nic.ru/rdap/ is seems broken for robots
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNotNull($data->registrarServerException);
        self::assertEquals('Can\'t load info from registrar server.', $data->registrarServerException->getMessage());
        self::assertEquals('https://www.nic.ru/rdap', $data->registrarServer->server);
        self::assertNull($data->registrarResponse);
        self::assertIsArray($data->response->data); // RdapResponse
        self::assertEquals('https://www.nic.ru/rdap/domain/VK.COM', $data->response->data['links'][1]['href']);
        self::assertEquals('https://rdap.verisign.com/com/v1/', $data->server->server);
    }

    public function testRu(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('ru');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertNull($data->registrarServer);
        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('["org",[],"text","Coordination Center for TLD RU"]', $data->getResponseAsString());
        self::assertEquals('https://rdap.iana.org', $data->server->server); // todo: hardcoded server for root tld
    }

    public function testLocalhost(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $this->expectException(NetworkException::class);
        $data = $handler->process('localhost');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
    }

    public function testVkCom(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('vk.com');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertEquals('whois.nic.ru', $data->registrarServer->server);
        self::assertStringContainsString('Registrar URL: http://www.nic.ru', $data->registrarResponse->data);

        self::assertStringContainsString('Registrar WHOIS Server: whois.nic.ru', $data->response->data);
        self::assertEquals('whois.verisign-grs.com', $data->server->server);
    }

    // non UTF-8 server
    public function testRegistroBr(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('registro.br');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertNull($data->registrarServer);
        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('Núcleo de Inf. e Coord. do Ponto BR - NIC.BR', $data->getResponseAsString());
        self::assertEquals('whois.registro.br', $data->server->server);
    }

    // rdap server
    public function testNicTjmaxx(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('nic.tjmaxx');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertNull($data->registrarServer);
        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('"events":[{"eventAction":"registration","eventDate":"2013-08-09T22:59:03Z","eventActor":"The TJX Companies, Inc."}', $data->getResponseAsString());
        self::assertEquals('https://rdap.nic.tjmaxx/', $data->server->server);
    }

    // punycode
    public function testPrzidentRf(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('президент.рф');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertNull($data->registrarServer);
        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('org:           Special Communications and Information Service of the Federal Guard Service of the Russian Federation (Spetssvyaz FSO RF)', $data->getResponseAsString());
        self::assertEquals('whois.tcinet.ru', $data->server->server);
    }

    // force whois server
    public function testSirusSu(): void
    {
        $serverList = self::createServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('sirus.su', new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS));
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarServerException);
        self::assertNull($data->registrarServer);
        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('e-mail:        sir.nyll@gmail.com', $data->getResponseAsString());
        self::assertEquals('whois.tcinet.ru', $data->server->server);
    }
}
