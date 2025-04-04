<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\EmptyQueryException;
use PHPWhoisLite\Handler\AsnHandler;
use PHPWhoisLite\Handler\DomainHandler;
use PHPWhoisLite\Handler\IpHandler;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\Resource\AsnServerList;
use PHPWhoisLite\Resource\IpServerList;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\TldServerList;
use PHPWhoisLite\Response\AsnResponse;
use PHPWhoisLite\Response\DomainResponse;
use PHPWhoisLite\Response\IpResponse;

final readonly class Whois
{
    public function __construct(
        private ?NetworkClient $networkClient = null,
        private ?TldServerList $tldServerList = null,
        private ?AsnServerList $asnServerList = null,
        private ?IpServerList $ipServerList = null,
    ) {
    }

    /**
     * @throws EmptyQueryException
     */
    public function process(string $query, ?Server $forceServer = null): IpResponse|AsnResponse|DomainResponse
    {
        return $this->createQueryHandler($query)->process($query, $forceServer);
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
            $ipServerList = $this->ipServerList ?? new IpServerList();

            return new IpHandler($networkClient, $ipServerList);
        }
        if ($this->isAsn($query)) {
            $asnServerList = $this->asnServerList ?? new AsnServerList();

            return new AsnHandler($networkClient, $asnServerList);
        }

        $tldServerList = $this->tldServerList ?? new TldServerList();

        return new DomainHandler($networkClient, $tldServerList);
    }

    private function isAsn(string $query): bool
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
