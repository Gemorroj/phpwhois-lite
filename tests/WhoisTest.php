<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\TestCase;
use PHPWhoisLite\Client\WhoisClient;
use PHPWhoisLite\Exception\IpReservedRangeException;
use PHPWhoisLite\Whois;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class WhoisTest extends TestCase
{
    private function createLoggedClient(): WhoisClient
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
        $outputStyle = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($outputStyle);

        $cache = new FilesystemAdapter('phpwhois-lite', 60);

        return new WhoisClient(cache: $cache, logger: $logger);
    }

    public function testIp127001(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $this->expectException(IpReservedRangeException::class);
        $data = $whois->lookup('127.0.0.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testIpApnic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('1.1.1.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testIpArin(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('2001:4860:4860::8888');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testIpRipe(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('193.0.11.51');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    // todo
    public function testIpLacnic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('200.3.13.10');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testIpAfrinic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('196.216.2.1');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testAsApnic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('AS4837');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testAsArin(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('220');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testAsRipe(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('AS3333');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    // todo
    public function testAsLacnic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('AS28001');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }

    public function testAsAfrinic(): void
    {
        $whois = new Whois($this->createLoggedClient());
        $data = $whois->lookup('AS33764');
        // \file_put_contents('/test.txt', $data->raw);
        // var_dump($data->raw);
    }
}
