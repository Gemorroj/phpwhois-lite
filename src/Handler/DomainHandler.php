<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\Exception\InvalidCharacterException as Algo26InvalidCharacterException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Data;
use PHPWhoisLite\Exception\InvalidCharacterException;
use PHPWhoisLite\Exception\InvalidWhoisServerException;
use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\Resource\WhoisServerList;
use PHPWhoisLite\WhoisClient;
use PHPWhoisLite\WhoisServerDetectorTrait;
use Psr\Cache\InvalidArgumentException;

final readonly class DomainHandler implements HandlerInterface
{
    use WhoisServerDetectorTrait;

    public function __construct(private WhoisClient $whoisClient, private WhoisServerList $whoisServerList = new WhoisServerList())
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidCharacterException
     * @throws QueryRateLimitExceededException
     * @throws TimeoutException
     * @throws NetworkException
     * @throws InvalidWhoisServerException
     */
    public function process(string $query, ?string $forceWhoisServer = null): Data
    {
        try {
            $query = (new ToIdn())->convert($query);
        } catch (AlreadyPunycodeException) {
            // $query is already a Punycode
        } catch (Algo26InvalidCharacterException $e) {
            throw new InvalidCharacterException('Invalid query: '.$query, previous: $e);
        }

        if (null !== $forceWhoisServer) {
            $server = $this->prepareWhoisServer($forceWhoisServer);
        } else {
            $server = $this->findServer($this->whoisServerList, $query);
        }

        $raw = $this->whoisClient->getData($server, $query);

        if (!$forceWhoisServer) {
            $registrarServer = $this->findRegistrarServer($raw);
            if ($registrarServer && $registrarServer !== $server) {
                $raw = $this->whoisClient->getData($registrarServer, $query);
            }
        }

        return new Data(
            $raw,
            $registrarServer ?? $server,
            QueryTypeEnum::DOMAIN,
        );
    }
}
