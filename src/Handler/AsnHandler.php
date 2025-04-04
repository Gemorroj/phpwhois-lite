<?php

declare(strict_types=1);

namespace WhoRdap\Handler;

use Psr\Cache\InvalidArgumentException;
use WhoRdap\Exception\NetworkException;
use WhoRdap\Exception\QueryRateLimitExceededException;
use WhoRdap\Exception\TimeoutException;
use WhoRdap\HandlerInterface;
use WhoRdap\NetworkClient\NetworkClient;
use WhoRdap\Resource\AsnServerList;
use WhoRdap\Resource\Server;
use WhoRdap\Resource\ServerTypeEnum;
use WhoRdap\Response\AsnResponse;

final readonly class AsnHandler implements HandlerInterface
{
    public function __construct(private NetworkClient $networkClient, private AsnServerList $serverList = new AsnServerList())
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws TimeoutException
     * @throws QueryRateLimitExceededException
     * @throws NetworkException
     * @throws \JsonException
     */
    public function process(string $query, ?Server $forceServer = null): AsnResponse
    {
        $server = $forceServer ?? $this->findAsnServer($query);

        $q = $this->prepareServerQuery($server, $query);
        $response = $this->networkClient->getResponse($server, $q);

        return new AsnResponse(
            $response,
            $server,
        );
    }

    private function findAsnServer(string $query): Server
    {
        $hasAsPrefix = false !== \stripos($query, 'AS');
        $number = $hasAsPrefix ? \substr($query, 2) : $query;
        $number = (int) $number;

        foreach ($this->serverList->servers as $range => $server) {
            if (!\is_int($range) && \str_contains($range, '-')) {
                [$fromRange, $toRange] = \explode('-', $range, 2);
            } else {
                $fromRange = $toRange = $range;
            }

            if ($number >= $fromRange && $number <= $toRange) {
                return $server;
            }
        }

        return $this->serverList->serverDefault;
    }

    private function prepareServerQuery(Server $server, string $query): string
    {
        if (ServerTypeEnum::RDAP === $server->type) {
            $hasAsPrefix = false !== \stripos($query, 'AS');

            return '/autnum/'.($hasAsPrefix ? \substr($query, 2) : $query);
        }
        if (ServerTypeEnum::WHOIS === $server->type && \in_array($server->server, ['whois.arin.net', 'whois.arin.net:43'], true)) {
            $hasAsPrefix = false !== \stripos($query, 'AS');

            return $hasAsPrefix ? 'z '.\substr($query, 2) : 'z '.$query;
        }

        return $query;
    }
}
