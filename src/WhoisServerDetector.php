<?php

declare(strict_types=1);

namespace PHPWhoisLite;

trait WhoisServerDetector
{
    private const BASE_SERVERS = [
        'European Regional Internet Registry/RIPE NCC' => 'whois.ripe.net',
        'RIPE Network Coordination Centre' => 'whois.ripe.net',
        'Asia Pacific Network Information	Center' => 'whois.apnic.net',
        'Asia Pacific Network Information Centre' => 'whois.apnic.net',
        'Latin American and Caribbean IP address Regional Registry' => 'whois.lacnic.net',
        'African Network Information Center' => 'whois.afrinic.net',
        'American Registry for Internet Numbers' => 'whois.arin.net',
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
