<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\Resource\IpServerList;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\IpResponse;
use Psr\Cache\InvalidArgumentException;

final readonly class IpHandler implements HandlerInterface
{
    public function __construct(private NetworkClient $networkClient, private IpServerList $serverList = new IpServerList())
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws TimeoutException
     * @throws QueryRateLimitExceededException
     * @throws NetworkException
     * @throws \JsonException
     */
    public function process(string $query, ?Server $forceServer = null): IpResponse
    {
        $server = $forceServer ?? $this->findIpServer($query);

        $q = $this->prepareServerQuery($server, $query);
        $response = $this->networkClient->getResponse($server, $q);

        return new IpResponse(
            $response,
            $server,
        );
    }

    private function findIpServer(string $query): Server
    {
        $slashPos = \strpos($query, '/'); // skip query CIDR
        if (false === $slashPos) {
            $ip = $query;
        } else {
            $ip = \substr($query, 0, $slashPos);
        }

        $isIpv6 = \substr_count($query, ':');
        if ($isIpv6) {
            return $this->findIpv6Server($ip) ?? $this->serverList->serverDefault;
        }

        return $this->findIpv4Server($ip) ?? $this->serverList->serverDefault;
    }

    private function findIpv4Server(string $ip): ?Server
    {
        foreach ($this->serverList->serversIpv4 as $ipv4Cidr => $server) {
            $parts = \explode('/', $ipv4Cidr, 2);
            $subnet = $parts[0];
            $mask = (int) ($parts[1] ?? 32);

            $address = \ip2long($ip);
            $subnetAddress = \ip2long($subnet);
            $mask = -1 << (32 - $mask);
            $subnetAddress &= $mask; // nb: in case the supplied subnet wasn't correctly aligned
            $match = ($address & $mask) === $subnetAddress;
            if ($match) {
                return $server;
            }
        }

        return null;
    }

    private function findIpv6Server(string $ip): ?Server
    {
        foreach ($this->serverList->serversIpv6 as $ipv6Cidr => $server) {
            $parts = \explode('/', $ipv6Cidr, 2);
            $subnet = $parts[0];
            $mask = (int) ($parts[1] ?? 128);

            $subnet = \inet_pton($subnet);
            $addr = \inet_pton($ip);

            $binMask = \str_repeat('f', \intdiv($mask, 4));
            switch ($mask % 4) {
                case 1:
                    $binMask .= '8';
                    break;
                case 2:
                    $binMask .= 'c';
                    break;
                case 3:
                    $binMask .= 'e';
                    break;
            }
            $binMask = \str_pad($binMask, 32, '0');
            $binMask = \pack('H*', $binMask);

            $match = ($addr & $binMask) === $subnet;
            if ($match) {
                return $server;
            }
        }

        return null;
    }

    private function prepareServerQuery(Server $server, string $query): string
    {
        if (ServerTypeEnum::RDAP === $server->type) {
            return '/ip/'.$query;
        }
        if (ServerTypeEnum::WHOIS === $server->type && \in_array($server->server, ['whois.arin.net', 'whois.arin.net:43'], true)) {
            $isCidr = \str_contains($query, '/');

            return $isCidr ? 'r = '.$query : 'z '.$query;
        }

        return $query;
    }
}
