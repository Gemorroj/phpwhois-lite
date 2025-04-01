<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use PHPWhoisLite\Data;
use PHPWhoisLite\Exception\InvalidWhoisServerException;
use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\WhoisClient;
use PHPWhoisLite\WhoisServerDetectorTrait;
use Psr\Cache\InvalidArgumentException;

final readonly class AsHandler implements HandlerInterface
{
    use WhoisServerDetectorTrait;

    public function __construct(private WhoisClient $whoisClient, private string $defaultWhoisServer = 'whois.arin.net:43')
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws TimeoutException
     * @throws QueryRateLimitExceededException
     * @throws NetworkException
     * @throws InvalidWhoisServerException
     */
    public function process(string $query, ?string $forceWhoisServer = null): Data
    {
        if (null !== $forceWhoisServer) {
            $server = $this->prepareWhoisServer($forceWhoisServer);
        } else {
            $server = $this->defaultWhoisServer;
        }

        $q = $this->prepareServerQuery($server, $query);
        $raw = $this->whoisClient->getData($server, $q);

        if (!$forceWhoisServer) {
            $baseServer = $this->findBaseServer($raw);
            if ($baseServer && $baseServer !== $server) {
                $q = $this->prepareServerQuery($baseServer, $query);
                $raw = $this->whoisClient->getData($baseServer, $q);
            }
        }

        return new Data(
            $raw,
            $baseServer ?? $server,
            QueryTypeEnum::AS,
        );
    }

    private function prepareServerQuery(string $server, string $query): string
    {
        $hasAsPrefix = false !== \stripos($query, 'AS');

        return match ($server) {
            'whois.ripe.net:43' => $query,
            'whois.apnic.net:43' => $query,
            'whois.lacnic.net:43' => $query,
            'whois.afrinic.net:43' => $query,
            'whois.arin.net:43' => $hasAsPrefix ? 'a '.\substr($query, 2) : 'a '.$query,
            default => $query,
        };
    }
}
