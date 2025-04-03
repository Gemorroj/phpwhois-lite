<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\EmptyQueryException;
use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;

final readonly class Whois
{
    public function __construct(private ?NetworkClient $networkClient = null, private ?ServerList $serverList = null)
    {
    }

    /**
     * @throws EmptyQueryException
     */
    public function process(string $query, ?Server $forceWhoisServer = null): Data
    {
        return $this->createQueryHandler($query)->process($query, $forceWhoisServer);
    }

    /**
     * @throws EmptyQueryException
     */
    private function createQueryHandler(string $query): HandlerInterface
    {
        if ('' === $query) {
            return throw new EmptyQueryException('The query is empty');
        }

        $networkClient = $this->networkClient ?? new NetworkClient();

        if ($this->isIp($query)) {
            return new IpHandler($networkClient);
        }
        if ($this->isAs($query)) {
            return new AsHandler($networkClient);
        }

        $serverList = $this->serverList ?? new ServerList();

        return new DomainHandler($networkClient, $serverList);
    }

    private function isAs(string $query): bool
    {
        $hasAsPrefix = false !== \stripos($query, 'AS');
        if ($hasAsPrefix) {
            $query = \substr($query, 2);
        }

        if (\preg_match('/^\d+$/', $query)) {
            return true;
        }

        return false;
    }

    private function isIp(string $query): bool
    {
        if (\str_contains($query, '/')) { // check CIDR
            $parts = \explode('/', $query);
            if (2 !== \count($parts)) {
                return false;
            }

            $ip = $parts[0];
            $netmask = (int) $parts[1];
            if ($netmask < 0) {
                return false;
            }

            if (\filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4])) {
                return $netmask <= 32;
            }

            if (\filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV6])) {
                return $netmask <= 128;
            }

            return false;
        }

        return false !== \filter_var($query, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6]);
    }
}
