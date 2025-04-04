<?php

namespace WhoRdap\Tests\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use WhoRdap\Exception\NetworkException;
use WhoRdap\Exception\RegistrarServerException;
use WhoRdap\Handler\DomainHandler;
use WhoRdap\NetworkClient\RdapResponse;
use WhoRdap\NetworkClient\WhoisResponse;
use WhoRdap\Resource\Server;
use WhoRdap\Resource\ServerTypeEnum;
use WhoRdap\Resource\TldServerList;
use WhoRdap\Tests\BaseTestCase;

final class DomainHandlerTest extends BaseTestCase
{
    #[DataProvider('getDomains')]
    public function testFindTldServer(string $query, ?Server $server): void
    {
        $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findTldServer');
        $result = $reflectionMethod->invoke($handler, $query);

        self::assertEquals($server, $result);
    }

    public static function getDomains(): \Generator
    {
        yield ['vk.com', new Server('whois.verisign-grs.com', ServerTypeEnum::WHOIS)];
        yield ['ya.ru', new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS)];
        yield ['test.org.ru', new Server('whois.nic.ru', ServerTypeEnum::WHOIS)];
        yield ['test.tjmaxx', new Server('https://rdap.nic.tjmaxx/', ServerTypeEnum::RDAP)];
        yield ['ru', self::createTldServerList()->serverDefault];
        yield ['domain.unknowntld', self::createTldServerList()->serverDefault];
    }

    #[DataProvider('getRegistrarData')]
    public function testFindRegistrarServer(RdapResponse|WhoisResponse $response, ?Server $expectedServer): void
    {
        $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
        $reflectionObject = new \ReflectionObject($handler);
        $reflectionMethod = $reflectionObject->getMethod('findRegistrarServer');
        $result = $reflectionMethod->invoke($handler, $response);

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
            $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
            $reflectionObject = new \ReflectionObject($handler);
            $reflectionMethod = $reflectionObject->getMethod('prepareWhoisServer');
            $result = $reflectionMethod->invoke($handler, $server);
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

    public function testRegistrarServerException(): void
    {
        $serverList = new TldServerList();
        $serverList->serverDefault = new Server('https://rdap.iana.org', ServerTypeEnum::RDAP);
        $serverList->servers = [
            '.com' => new Server('https://rdap.verisign.com/com/v1/', ServerTypeEnum::RDAP),
        ];

        $handler = new DomainHandler($this->createLoggedClient(), $serverList);
        $data = $handler->process('vk.com'); // https://www.nic.ru/rdap/ is seems broken for robots
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertInstanceOf(RegistrarServerException::class, $data->registrarResponse);
        self::assertEquals('Can\'t load info from registrar server.', $data->registrarResponse->getMessage());
        self::assertIsArray($data->response->data); // RdapResponse
        self::assertEquals('https://www.nic.ru/rdap/domain/VK.COM', $data->response->data['links'][1]['href']);
        self::assertEquals('https://rdap.verisign.com/com/v1/', $data->server->server);
    }

    public function testLocalhost(): void
    {
        $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
        $this->expectException(NetworkException::class);
        $data = $handler->process('localhost');
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());
    }

    // force whois server
    public function testSirusSu(): void
    {
        $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
        $data = $handler->process('sirus.su', new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS));
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertNull($data->registrarResponse);
        self::assertStringContainsString('e-mail:        sir.nyll@gmail.com', $data->getResponseAsString());
        self::assertEquals('whois.tcinet.ru', $data->server->server);
    }

    #[DataProvider('getDomainResponse')]
    public function testProcess(
        string $query,
        string $expectedServer,
        string $expectedResponse,
        ?string $expectedRegistrarServer,
        ?string $expectedRegistrarResponse,
    ): void {
        $handler = new DomainHandler($this->createLoggedClient(), self::createTldServerList());
        $data = $handler->process($query);
        // \file_put_contents('/test.txt', $data->getResponseAsString());
        // var_dump($data->getResponseAsString());

        self::assertEquals($expectedServer, $data->server->server);
        self::assertStringContainsString($expectedResponse, $data->getResponseAsString());
        self::assertEquals($expectedRegistrarServer, $data->registrarResponse?->server->server);
        if (null === $expectedRegistrarResponse) {
            self::assertNull($data->registrarResponse?->getResponseAsString());
        } else {
            self::assertStringContainsString($expectedRegistrarResponse, $data->registrarResponse?->getResponseAsString());
        }
    }

    public static function getDomainResponse(): \Generator
    {
        yield ['ru', self::createTldServerList()->serverDefault->server, '"Coordination Center for TLD RU"', null, null];
        yield ['vk.com', 'whois.verisign-grs.com', 'Registrar URL: http://nic.ru', 'whois.nic.ru', 'Registrant Country: RU'];
        yield ['registro.br', 'whois.registro.br', 'Núcleo de Inf. e Coord. do Ponto BR - NIC.BR', null, null]; // non UTF-8
        yield ['nic.tjmaxx', 'https://rdap.nic.tjmaxx/', '"eventActor": "The TJX Companies, Inc."', null, null]; // rdap server
        yield ['президент.рф', 'whois.tcinet.ru', 'org:           Special Communications and Information Service of the Federal Guard Service of the Russian Federation (Spetssvyaz FSO RF)', null, null]; // punycode
    }

    private static function createTldServerList(): TldServerList
    {
        $serverList = new TldServerList();
        $serverList->serverDefault = new Server('https://rdap.iana.org', ServerTypeEnum::RDAP);
        $serverList->servers = [
            '.com' => new Server('whois.verisign-grs.com', ServerTypeEnum::WHOIS),
            '.ru' => new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS),
            '.org.ru' => new Server('whois.nic.ru', ServerTypeEnum::WHOIS),
            '.br' => new Server('whois.registro.br', ServerTypeEnum::WHOIS),
            '.tjmaxx' => new Server('https://rdap.nic.tjmaxx/', ServerTypeEnum::RDAP),
            '.xn--p1ai' => new Server('whois.tcinet.ru', ServerTypeEnum::WHOIS),
        ];

        return $serverList;
    }
}
