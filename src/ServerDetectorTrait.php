<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\InvalidWhoisServerException;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerList;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\RdapResponse;
use PHPWhoisLite\Response\WhoisResponse;

trait ServerDetectorTrait
{
    protected function findBaseServer(RdapResponse|WhoisResponse $response): ?Server
    {
        // https://rdap.verisign.com/com/v1/domain/vk.com
        if ($response instanceof RdapResponse) {
            // https://datatracker.ietf.org/doc/html/rfc9083#name-links
            $links = $response->data['links'] ?? [];
            foreach ($links as $link) {
                if ($link['href'] && 'related' === $link['rel'] && 'application/rdap+json' === $link['type']) {
                    $href = \strtolower($link['href']);

                    $posIp = \strpos($href, '/ip/');
                    if (false !== $posIp) {
                        $href = \substr($href, 0, $posIp);
                    }
                    $posAutnum = \strpos($href, '/autnum/');
                    if (false !== $posAutnum) {
                        $href = \substr($href, 0, $posAutnum);
                    }

                    return new Server($href, ServerTypeEnum::RDAP);
                }
            }
            // https://datatracker.ietf.org/doc/html/rfc9083#section-4.7
            /*if (isset($response->data['port43'])) {
                return new Server($response->data['port43'], ServerTypeEnum::WHOIS);
            }*/

            return null;
        }

        // todo: use rdap servers as default
        $baseServers = [
            'European Regional Internet Registry/RIPE NCC' => new Server('whois.ripe.net', ServerTypeEnum::WHOIS),
            'RIPE Network Coordination Centre' => new Server('whois.ripe.net', ServerTypeEnum::WHOIS),
            'Asia Pacific Network Information	Center' => new Server('whois.apnic.net', ServerTypeEnum::WHOIS),
            'Asia Pacific Network Information Centre' => new Server('whois.apnic.net', ServerTypeEnum::WHOIS),
            'Latin American and Caribbean IP address Regional Registry' => new Server('whois.lacnic.net', ServerTypeEnum::WHOIS),
            'African Network Information Center' => new Server('whois.afrinic.net', ServerTypeEnum::WHOIS),
            'American Registry for Internet Numbers' => new Server('whois.arin.net', ServerTypeEnum::WHOIS),
        ];

        foreach ($baseServers as $str => $s) {
            if (\str_contains($response->data, $str)) {
                return $s;
            }
        }

        return null;
    }

    protected function findServer(ServerList $serverList, string $query): Server
    {
        if (0 === \substr_count($query, '.')) {
            return new Server('https://rdap.iana.org', ServerTypeEnum::RDAP);
        }

        $dp = \explode('.', $query);
        $np = \count($dp) - 1;
        $tldTests = [];

        for ($i = 0; $i < $np; ++$i) {
            \array_shift($dp);
            $tldTests[] = '.'.\implode('.', $dp);
        }

        foreach ($tldTests as $tld) {
            if (isset($serverList->servers[$tld])) {
                return $serverList->servers[$tld];
            }
        }

        return $serverList->serverDefault;
    }

    private function findRegistrarServer(RdapResponse|WhoisResponse $response): ?Server
    {
        // https://rdap.verisign.com/com/v1/domain/vk.com
        if ($response instanceof RdapResponse) {
            // https://datatracker.ietf.org/doc/html/rfc9083#name-links
            $links = $response->data['links'] ?? [];
            foreach ($links as $link) {
                if ($link['href'] && 'related' === $link['rel'] && 'application/rdap+json' === $link['type']) {
                    $href = \strtolower($link['href']);
                    $posDomain = \strpos($href, '/domain/');
                    if (false !== $posDomain) {
                        $href = \substr($href, 0, $posDomain);
                    }

                    return new Server($href, ServerTypeEnum::RDAP);
                }
            }
            // https://datatracker.ietf.org/doc/html/rfc9083#section-4.7
            /*if (isset($response->data['port43'])) {
                return new Server($response->data['port43'], ServerTypeEnum::WHOIS);
            }*/

            return null;
        }

        $matches = [];
        if (\preg_match('/Registrar WHOIS Server:(.+)/i', $response->data, $matches)) {
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
    private function prepareWhoisServer(string $whoisServer): Server
    {
        $whoisServer = \strtolower($whoisServer);

        if (\str_starts_with($whoisServer, 'rwhois://')) {
            $whoisServer = \substr($whoisServer, 9);
        }
        if (\str_starts_with($whoisServer, 'whois://')) {
            $whoisServer = \substr($whoisServer, 8);
        }

        $parsedWhoisServer = \parse_url($whoisServer);
        if (isset($parsedWhoisServer['scheme'])) {
            throw new InvalidWhoisServerException('Invalid WHOIS server path.');
        }
        if (isset($parsedWhoisServer['path']) && $parsedWhoisServer['path'] === $whoisServer) {
            // https://stackoverflow.com/questions/1418423/the-hostname-regex
            if (!\preg_match('/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/', $parsedWhoisServer['path'])) {
                // something strange path. /passwd for example
                throw new InvalidWhoisServerException('Invalid WHOIS server path.');
            }
        }

        return new Server(
            $whoisServer,
            ServerTypeEnum::WHOIS,
        );
    }
}
