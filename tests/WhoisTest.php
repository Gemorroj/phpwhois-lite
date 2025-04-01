<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\Whois;

final class WhoisTest extends BaseTestCase
{
    #[DataProvider('getQueries')]
    public function testReservedRangeIp(string $query, string $className): void
    {
        $whois = new Whois();
        $reflectionObject = new \ReflectionObject($whois);
        $reflectionMethod = $reflectionObject->getMethod('createQueryHandler');
        $handler = $reflectionMethod->invoke($whois, $query);

        self::assertEquals($handler::class, $className);
    }

    public static function getQueries(): \Generator
    {
        yield ['127.0.0.1', IpHandler::class];
        yield ['192.168.0.1', IpHandler::class];
        yield ['1.1.1.1', IpHandler::class];
        yield ['AS220', AsHandler::class];
        yield ['12345', AsHandler::class];
        yield ['ya.ru', DomainHandler::class];
    }
}
