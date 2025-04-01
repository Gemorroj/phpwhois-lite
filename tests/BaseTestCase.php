<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\TestCase;
use PHPWhoisLite\Client\WhoisClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseTestCase extends TestCase
{
    protected function createLoggedClient(): WhoisClient
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
        $outputStyle = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($outputStyle);

        $cache = new FilesystemAdapter('phpwhois-lite', 60);

        return new WhoisClient(cache: $cache, logger: $logger);
    }
}
