<?php

declare(strict_types=1);

namespace PHPWhoisLite\Handler;

use PHPWhoisLite\Client\WhoisClient;
use PHPWhoisLite\Data;
use PHPWhoisLite\HandlerInterface;
use PHPWhoisLite\QueryTypeEnum;
use PHPWhoisLite\WhoisServerDetector;

final readonly class AsHandler implements HandlerInterface
{
    use WhoisServerDetector;

    public function __construct(private WhoisClient $whoisClient, private string $defaultWhoisServer = 'whois.arin.net:43')
    {
    }

    public function process(string $query): ?Data
    {
        $q = $this->prepareServerQuery($this->defaultWhoisServer, $query);
        $raw = $this->whoisClient->getData($this->defaultWhoisServer, $q);
        if (null === $raw) {
            return null;
        }

        $findServer = $this->findBaseServer($raw);
        if ($findServer && $findServer !== $this->defaultWhoisServer) {
            $q = $this->prepareServerQuery($findServer, $query);
            $raw = $this->whoisClient->getData($findServer, $q);
            if (null === $raw) {
                return null;
            }
        }

        return new Data(
            $raw,
            $findServer ?? $this->defaultWhoisServer,
            QueryTypeEnum::AS,
        );
    }

    private function prepareServerQuery(string $server, string $query): string
    {
        $hasAsPrefix = false !== \stripos($query, 'AS');

        switch ($server) {
            case 'whois.ripe.net:43':
                return $query;
                break;
            case 'whois.apnic.net:43':
                return $query;
                break;
            case 'whois.lacnic.net:43':
                return $query; // fixme: server is down?
                break;
            case 'whois.afrinic.net:43':
                return $query;
                break;
            case 'whois.arin.net:43':
                if ($hasAsPrefix) {
                    return 'a '.\substr($query, 2);
                }

                return 'a '.$query;
                break;
            default:
                return $query;
                break;
        }
    }
}
