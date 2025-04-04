<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\IpResponse;
use PHPWhoisLite\ServerDetectorTrait;
use Psr\Cache\InvalidArgumentException;

final readonly class IpHandler implements HandlerInterface
{
    use ServerDetectorTrait;

    public function __construct(private NetworkClient $networkClient, private Server $defaultServer = new Server('whois.arin.net:43', ServerTypeEnum::WHOIS))
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
        $server = $forceServer ?? $this->defaultServer;

        $q = $this->prepareServerQuery($server, $query);
        $response = $this->networkClient->getResponse($server, $q);

        // todo: always use hardcoded servers by ICANN list. add it to ServerList
        if (!$forceServer) {
            $baseServer = $this->findBaseServer($response);
            if ($baseServer && !$baseServer->isEqual($server)) {
                $q = $this->prepareServerQuery($baseServer, $query);
                $response = $this->networkClient->getResponse($baseServer, $q);
            }
        }

        return new IpResponse(
            $response,
            $baseServer ?? $server,
        );
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
