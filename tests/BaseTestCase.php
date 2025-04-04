<?php

namespace PHPWhoisLite\Tests;

use PHPUnit\Framework\TestCase;
use PHPWhoisLite\NetworkClient\NetworkClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseTestCase extends TestCase
{
    protected function createLoggedClient(?int $cacheTime = null): NetworkClient
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
        $outputStyle = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($outputStyle);

        if (null === $cacheTime) {
            return new NetworkClient(logger: $logger);
        }

        $cache = new FilesystemAdapter('phpwhois-lite', $cacheTime);

        return new NetworkClient(cache: $cache, logger: $logger);
    }
}
