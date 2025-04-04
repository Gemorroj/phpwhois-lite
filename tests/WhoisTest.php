<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPWhoisLite\Handler\AsnHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\Whois;

final class WhoisTest extends BaseTestCase
{
    #[DataProvider('getQueries')]
    public function testCreateQueryHandler(string $query, string $className): void
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
        yield ['::/128 ', IpHandler::class];
        yield ['::ffff:0:0/96  ', IpHandler::class];
        yield ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', IpHandler::class];
        yield ['192.168.0.1', IpHandler::class];
        yield ['192.168.0.0/24', IpHandler::class];
        yield ['1.1.1.1', IpHandler::class];
        yield ['AS220', AsnHandler::class];
        yield ['12345', AsnHandler::class];
        yield ['ya.ru', DomainHandler::class];
        yield ['президент.рф', DomainHandler::class];
        yield ['.рф', DomainHandler::class];
        yield ['ru', DomainHandler::class];
    }
}
