<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\EmptyQueryException;
use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\Resource\WhoisServerList;

final readonly class Whois
{
    public function __construct(private ?WhoisClient $whoisClient = null, private ?WhoisServerList $whoisServerList = null)
    {
    }

    /**
     * @throws EmptyQueryException
     */
    public function process(string $query, ?string $forceWhoisServer = null): Data
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

        $whoisClient = $this->whoisClient ?? new WhoisClient();

        if ($this->isIp($query)) {
            return new IpHandler($whoisClient);
        }
        if ($this->isAs($query)) {
            return new AsHandler($whoisClient);
        }

        $whoisServerList = $this->whoisServerList ?? new WhoisServerList();

        return new DomainHandler($whoisClient, $whoisServerList);
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
        if (\str_contains($query, '/')) {
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
