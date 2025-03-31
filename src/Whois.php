<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\ToIdn;
use PHPWhoisLite\Client\WhoisClient;
use PHPWhoisLite\Handler\AsHandler;
use PHPWhoisLite\Handler\IpHandler;

final readonly class Whois
{
    public function __construct(private WhoisClient $whoisClient = new WhoisClient())
    {
    }

    /**
     * @throws \Algo26\IdnaConvert\Exception\InvalidCharacterException
     */
    public function lookup(string $query): ?Data
    {
        $queryType = $this->getQueryType($query);

        switch ($queryType) {
            case QueryType::IP:
                $handler = new IpHandler($this->whoisClient);

                return $handler->parse($query);
                break;
            case QueryType::AS:
                $handler = new AsHandler($this->whoisClient);

                return $handler->parse($query);
                break;
            case QueryType::DOMAIN:
                try {
                    $query = (new ToIdn())->convert($query);
                } catch (AlreadyPunycodeException $e) {
                    // $query is already a Punycode
                }
                $handler = new DomainHandler(); // todo
                break;
        }

        return null;
    }

    private function getQueryType(string $query): ?QueryType
    {
        if ($this->validIp($query, false)) {
            if ($this->validIp($query, true)) {
                return QueryType::IP;
            }

            return null;
        }

        if ('' !== $query) {
            if (\str_contains($query, '.')) {
                return QueryType::DOMAIN;
            }

            return QueryType::AS;
        }

        return null;
    }

    private function validIp(string $ip, bool $strict = true): bool
    {
        $flags = \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6;
        if ($strict) {
            $flags = \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6 | \FILTER_FLAG_NO_PRIV_RANGE/* | \FILTER_FLAG_NO_RES_RANGE */;
        }

        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, ['flags' => $flags]);
    }
}
