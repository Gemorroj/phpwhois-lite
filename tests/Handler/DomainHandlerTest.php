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
}
