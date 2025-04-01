<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\EmptyQueryException;
use PHPWhoisLite\Exception\IpPrivateRangeException;
use PHPWhoisLite\Exception\IpReservedRangeException;
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
     * @throws IpPrivateRangeException
     * @throws IpReservedRangeException
     */
    public function process(string $query): Data
    {
        return $this->createQueryHandler($query)->process($query);
    }

    /**
     * @throws EmptyQueryException
     * @throws IpPrivateRangeException
     * @throws IpReservedRangeException
     */
    private function createQueryHandler(string $query): HandlerInterface
    {
        if ('' === $query) {
            return throw new EmptyQueryException('The query is empty');
        }

        if ($this->validIp($query)) {
            if ($this->isIpPrivateRange($query)) {
                return throw IpPrivateRangeException::create($query);
            }
            if ($this->isIpReservedRange($query)) {
                return throw IpReservedRangeException::create($query);
            }

            $whoisClient = $this->whoisClient ?? new WhoisClient();

            return new IpHandler($whoisClient);
        }

        if (\str_contains($query, '.')) {
            $whoisClient = $this->whoisClient ?? new WhoisClient();
            $whoisServerList = $this->whoisServerList ?? new WhoisServerList();

            return new DomainHandler($whoisClient, $whoisServerList);
        }

        $whoisClient = $this->whoisClient ?? new WhoisClient();

        return new AsHandler($whoisClient);
    }

    private function validIp(string $ip): bool
    {
        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6]);
    }

    private function isIpPrivateRange(string $ip): bool
    {
        return false === \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6 | \FILTER_FLAG_NO_PRIV_RANGE]);
    }

    private function isIpReservedRange(string $ip): bool
    {
        return false === \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6 | \FILTER_FLAG_NO_RES_RANGE]);
    }
}
