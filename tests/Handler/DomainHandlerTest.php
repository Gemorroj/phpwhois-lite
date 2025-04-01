<?php

namespace PHPWhoisLite\Tests\Handler;

use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Resource\WhoisServerList;
use PHPWhoisLite\Tests\BaseTestCase;

final class DomainHandlerTest extends BaseTestCase
{
    public function testVkCom(): void
    {
        $whoisServerList = new WhoisServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $whoisServerList);
        $data = $handler->process('vk.com');
        // \file_put_contents('/test.txt', $data->raw);
        // \var_dump($data);
        self::assertStringContainsString('Registrar: Regional Network Information Center, JSC dba RU-CENTER', $data->raw);
        self::assertEquals('whois.nic.ru:43', $data->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // non UTF-8 server
    public function testRegistroBr(): void
    {
        $whoisServerList = new WhoisServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $whoisServerList);
        $data = $handler->process('registro.br');
        // \file_put_contents('/test.txt', $data->raw);
        // \var_dump($data);
        self::assertStringContainsString('Núcleo de Inf. e Coord. do Ponto BR - NIC.BR', $data->raw);
        self::assertEquals('whois.nic.br:43', $data->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }

    // http server
    public function testRegistryCoZa(): void
    {
        $whoisServerList = new WhoisServerList();
        $handler = new DomainHandler($this->createLoggedClient(), $whoisServerList);
        $data = $handler->process('registry.co.za');
        // \file_put_contents('/test.txt', $data->raw);
        // \var_dump($data);
        self::assertStringStartsNotWith('<pre>', $data->raw);
        self::assertStringContainsString('Registrar URL: www.TMU.com', $data->raw);
        self::assertEquals('http://whois.registry.net.za/whois/whois.sh?Domain=', $data->server);
        self::assertEquals(QueryTypeEnum::DOMAIN, $data->type);
    }
}
