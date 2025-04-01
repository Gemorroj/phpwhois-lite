<?php

namespace PHPWhoisLite\Tests;

use PHPWhoisLite\Exception\IpPrivateRangeException;
use PHPWhoisLite\Exception\IpReservedRangeException;
use PHPWhoisLite\Whois;

final class WhoisTest extends BaseTestCase
{
    public function testReservedRangeIp(): void
    {
        $whois = new Whois();
        $this->expectException(IpReservedRangeException::class);
        $data = $whois->process('127.0.0.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testPrivateRangeIp(): void
    {
        $whois = new Whois();
        $this->expectException(IpPrivateRangeException::class);
        $data = $whois->process('192.168.1.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }
}
