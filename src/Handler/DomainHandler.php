<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\Exception\InvalidCharacterException as Algo26InvalidCharacterException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Data;
use PHPWhoisLite\Exception\InvalidCharacterException;
use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\ServerDetectorTrait;
use Psr\Cache\InvalidArgumentException;

final readonly class DomainHandler implements HandlerInterface
{
    use ServerDetectorTrait;

    public function __construct(private NetworkClient $networkClient, private ServerList $serverList = new ServerList())
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidCharacterException
     * @throws QueryRateLimitExceededException
     * @throws TimeoutException
     * @throws NetworkException
     * @throws \JsonException
     */
    public function process(string $query, ?Server $forceServer = null): Data
    {
        try {
            $query = (new ToIdn())->convert($query);
        } catch (AlreadyPunycodeException) {
            // $query is already a Punycode
        } catch (Algo26InvalidCharacterException $e) {
            throw new InvalidCharacterException('Invalid query: '.$query, previous: $e);
        }

        $server = $forceServer ?? $this->findServer($this->serverList, $query);

        $q = $this->prepareServerQuery($server, $query);
        $response = $this->networkClient->getResponse($server, $q);

        if (!$forceServer) {
            $registrarServer = $this->findRegistrarServer($response);
            if ($registrarServer && !$registrarServer->isEqual($server)) {
                $q = $this->prepareServerQuery($registrarServer, $query);
                $response = $this->networkClient->getResponse($registrarServer, $q);
            }
        }

        return new Data(
            $response,
            $registrarServer ?? $server,
            QueryTypeEnum::DOMAIN,
        );
    }

    private function prepareServerQuery(Server $server, string $query): string
    {
        if (ServerTypeEnum::RDAP === $server->type) {
            return '/domain/'.$query;
        }

        return $query;
    }
}
