<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Client\WhoisClient;
use PHPWhoisLite\Exception\EmptyQueryException;
use PHPWhoisLite\Exception\IpPrivateRangeException;
use PHPWhoisLite\Exception\IpReservedRangeException;
use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;

final readonly class Whois
{
    public function __construct(private WhoisClient $whoisClient = new WhoisClient())
    {
    }

    public function lookup(string $query): ?Data
    {
        return $this->createQueryHandler($query)->parse($query);
    }

    private function createQueryHandler(string $query): HandlerInterface
    {
        if ($this->validIp($query)) {
            if ($this->isIpPrivateRange($query)) {
                return throw IpPrivateRangeException::create($query);
            }
            if ($this->isIpReservedRange($query)) {
                return throw IpReservedRangeException::create($query);
            }

            return new IpHandler($this->whoisClient);
        }

        if ('' !== $query) {
            if (\str_contains($query, '.')) {
                return new DomainHandler();
            }

            return new AsHandler($this->whoisClient);
        }

        return throw new EmptyQueryException('The query is empty');
    }

    private function validIp(string $ip): bool
    {
        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6]);
    }

    private function isIpPrivateRange(string $ip): bool
    {
        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6 | \FILTER_FLAG_NO_PRIV_RANGE]);
    }

    private function isIpReservedRange(string $ip): bool
    {
        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6 | \FILTER_FLAG_NO_RES_RANGE]);
    }
}
