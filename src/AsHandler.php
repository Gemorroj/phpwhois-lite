<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Client\WhoisClient;

final readonly class AsHandler implements HandlerInterface
{
    use WhoisServerDetector;

    public function __construct(private string $defaultWhoisServer = 'whois.arin.net', private WhoisClient $whoisClient = new WhoisClient())
    {
    }

    public function parse(string $query): ?Data
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

        return new Data($raw);
    }

    private function prepareServerQuery(string $server, string $query): string
    {
        $hasAsPrefix = false !== \stripos($query, 'AS');

        switch ($server) {
            case 'whois.ripe.net':
                return $query;
                break;
            case 'whois.apnic.net':
                return $query;
                break;
            case 'whois.lacnic.net':
                return $query; // fixme: server is down?
                break;
            case 'whois.afrinic.net':
                return $query;
                break;
            case 'whois.arin.net':
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
