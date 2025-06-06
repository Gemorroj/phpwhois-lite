<?php

namespace WhoRdap\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WhoRdap\NetworkClient\NetworkClient;

abstract class BaseTestCase extends TestCase
{
    protected function createLoggedClient(?int $cacheTime = null, ?int $timeout = 5): NetworkClient
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
        $outputStyle = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($outputStyle);

        if (null === $cacheTime) {
            return new NetworkClient(logger: $logger);
        }

        $cache = new FilesystemAdapter('whordap', $cacheTime);

        return new NetworkClient(timeout: $timeout, cache: $cache, logger: $logger);
    }
}
