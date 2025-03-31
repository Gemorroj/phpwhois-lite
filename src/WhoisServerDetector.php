<?php

declare(strict_types=1);

namespace PHPWhoisLite;

trait WhoisServerDetector
{
    private const BASE_SERVERS = [
        'European Regional Internet Registry/RIPE NCC' => 'whois.ripe.net:43',
        'RIPE Network Coordination Centre' => 'whois.ripe.net:43',
        'Asia Pacific Network Information	Center' => 'whois.apnic.net:43',
        'Asia Pacific Network Information Centre' => 'whois.apnic.net:43',
        'Latin American and Caribbean IP address Regional Registry' => 'whois.lacnic.net:43',
        'African Network Information Center' => 'whois.afrinic.net:43',
        'American Registry for Internet Numbers' => 'whois.arin.net:43',
    ];

    private function findBaseServer(string $raw): ?string
    {
        foreach (self::BASE_SERVERS as $name => $s) {
            if (\str_contains($raw, $name)) {
                return $s;
            }
        }

        return null;
    }
}
