<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\Exception\InvalidCharacterException as Algo26InvalidCharacterException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Exception\InvalidCharacterException;
use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\RegistrarServerException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\NetworkClient\NetworkClient;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\DomainResponse;
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
    public function process(string $query, ?Server $forceServer = null): DomainResponse
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

        $registrarResponse = null;
        $registrarServerException = null;
        $registrarServer = null;
        if (!$forceServer) {
            $registrarServer = $this->findRegistrarServer($response);
            if ($registrarServer && !$registrarServer->isEqual($server)) {
                $q = $this->prepareServerQuery($registrarServer, $query);
                try {
                    $registrarResponse = $this->networkClient->getResponse($registrarServer, $q);
                } catch (\Exception $e) {
                    $registrarServerException = new RegistrarServerException('Can\'t load info from registrar server.', previous: $e);
                }
            }
        }

        return new DomainResponse(
            $response,
            $server,
            $registrarResponse,
            $registrarServer,
            $registrarServerException
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
