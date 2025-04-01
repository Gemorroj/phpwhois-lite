<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\InvalidWhoisServerException;
use PHPWhoisLite\Resource\WhoisServerList;

trait WhoisServerDetectorTrait
{
    protected const BASE_SERVERS = [
        'European Regional Internet Registry/RIPE NCC' => 'whois.ripe.net:43',
        'RIPE Network Coordination Centre' => 'whois.ripe.net:43',
        'Asia Pacific Network Information	Center' => 'whois.apnic.net:43',
        'Asia Pacific Network Information Centre' => 'whois.apnic.net:43',
        'Latin American and Caribbean IP address Regional Registry' => 'whois.lacnic.net:43',
        'African Network Information Center' => 'whois.afrinic.net:43',
        'American Registry for Internet Numbers' => 'whois.arin.net:43',
    ];

    protected function findBaseServer(string $raw): ?string
    {
        foreach (self::BASE_SERVERS as $name => $s) {
            if (\str_contains($raw, $name)) {
                return $s;
            }
        }

        return null;
    }

    protected function findServer(WhoisServerList $whoisServerList, string $query): ?string
    {
        $dp = \explode('.', $query);
        $np = \count($dp) - 1;
        $tldTests = [];

        for ($i = 0; $i < $np; ++$i) {
            \array_shift($dp);
            $tldTests[] = '.'.\implode('.', $dp);
        }

        foreach ($tldTests as $tld) {
            foreach ($whoisServerList->whoisServers as $whoisServer) {
                if ($whoisServer->tld === $tld) {
                    return $whoisServer->server;
                }
            }
        }

        foreach ($tldTests as $tld) {
            $cname = $tld.'.whois-servers.net';

            if (\gethostbyname($cname) === $cname) {
                continue;
            }

            return $tld.'.whois-servers.net';
        }

        return null;
    }

    private function findRegistrarServer(string $raw): ?string
    {
        $matches = [];
        if (\preg_match('/Registrar WHOIS Server:(.+)/i', $raw, $matches)) {
            $server = \trim($matches[1]);
            if ('' === $server) {
                return null;
            }

            try {
                return $this->prepareWhoisServer($server);
            } catch (InvalidWhoisServerException) {
                return null;
            }
        }

        return null;
    }

    /**
     * @throws InvalidWhoisServerException
     */
    private function prepareWhoisServer(string $server): string
    {
        $server = \strtolower($server);

        if (\str_starts_with($server, 'rwhois://')) {
            $server = \substr($server, 9);
        }
        if (\str_starts_with($server, 'whois://')) {
            $server = \substr($server, 8);
        }

        $parsedServer = \parse_url($server);
        if (isset($parsedServer['path']) && $parsedServer['path'] === $server) {
            // https://stackoverflow.com/questions/1418423/the-hostname-regex
            if (!\preg_match('/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/', $parsedServer['path'])) {
                // something strange path. /passwd for example
                throw new InvalidWhoisServerException('Invalid WHOIS server path.');
            }

            $server .= ':43'; // add default WHOIS port
        }
        if (isset($parsedServer['scheme'])) {
            if (!\in_array($parsedServer['scheme'], ['http', 'https'], true)) {
                // something strange scheme. file scheme for example
                throw new InvalidWhoisServerException('Invalid WHOIS server scheme. Allowed values are: rwhois, whois, http, https');
            }
        }

        return $server;
    }
}
