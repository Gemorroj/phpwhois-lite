<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\Resource\AsnServerList;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\AsnResponse;
use Psr\Cache\InvalidArgumentException;

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
