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

        if ($this->validIp($query)) {
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
}
