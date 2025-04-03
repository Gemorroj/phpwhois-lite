<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Resource\ServerTypeEnum;
use PHPWhoisLite\Response\RdapResponse;
use PHPWhoisLite\Response\WhoisResponse;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class NetworkClient implements NetworkClientInterface
{
    public function __construct(private int $timeout = 5, private int $buffer = 1024, private ?CacheItemPoolInterface $cache = null, private ?LoggerInterface $logger = null)
    {
    }

    protected function makeCacheKey(string ...$args): string
    {
        return \md5(\serialize($args));
    }

    /**
     * @throws InvalidArgumentException
     * @throws QueryRateLimitExceededException
     * @throws TimeoutException
     * @throws NetworkException
     * @throws \JsonException
     */
    public function getResponse(Server $server, string $query): RdapResponse|WhoisResponse
    {
        $cacheItem = null;
        if ($this->cache) {
            $cacheKey = $this->makeCacheKey($server->server, $server->type->value, $query);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $this->logger?->debug("Load cached data for $server->server -> $query");

                return $cacheItem->get();
            }
        }

        if (ServerTypeEnum::RDAP === $server->type) {
            $response = $this->rdapRequest($server, $query);
        } else {
            $response = $this->whoisRequest($server, $query);
        }

        if ($this->isQueryRateLimitExceeded($response)) {
            throw QueryRateLimitExceededException::create($server->server);
        }

        if ($cacheItem) {
            $this->logger?->debug('Save cache');
            $cacheItem->set($response);
            $this->cache->save($cacheItem);
        }

        return $response;
    }

    protected function isQueryRateLimitExceeded(RdapResponse|WhoisResponse $response): bool
    {
        if ($response instanceof WhoisResponse) {
            $stings = [
                'Query rate limit exceeded',
                'You have exceeded this limit',
                'Lookup quota exceeded',
                'WHOIS LIMIT EXCEEDED',
                'Excessive querying, grace period of',
                'Query limitation is',
                'exceeded allowed connection rate',
                'too many requests',
                'and will be replenished',
                'contained within a list of IP addresses that may have failed',
                'You exceeded the maximum',
                'Maximum Daily connection limit reached',
                'exceeded maximum connection limit',
                'Still in grace period, wait',
                'Query rate of ',
            ];
            foreach ($stings as $sting) {
                if (\str_contains($response->data, $sting)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function convertToUtf8(string $raw): string
    {
        $isUtf8 = (bool) \preg_match('//u', $raw);
        if (!$isUtf8) {
            $raw = \mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1'); // fixme: detect encoding. in fact mb_detect_encoding is not working
        }

        return $raw;
    }

    /**
     * @throws NetworkException
     * @throws \JsonException
     */
    protected function rdapRequest(Server $server, string $query): RdapResponse
    {
        $url = \rtrim($server->server, '/').$query;
        $this->logger?->debug("Initialize CURL connection to HTTP server: $url");
        $fp = \curl_init($url);
        if (!$fp) {
            $this->logger?->debug("Can't init CURL connection to HTTP server: $server->server");
            throw new NetworkException("Can't init CURL connection to HTTP server: $server->server");
        }
        \curl_setopt($fp, \CURLOPT_ENCODING, '');
        \curl_setopt($fp, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($fp, \CURLOPT_FOLLOWLOCATION, true); // http -> https
        \curl_setopt($fp, \CURLOPT_TIMEOUT, $this->timeout);
        \curl_setopt($fp, \CURLOPT_BUFFERSIZE, $this->buffer);
        \curl_setopt($fp, \CURLOPT_HTTPHEADER, [
            'User-Agent: PHPWhois Lite',
        ]);

        $this->logger?->debug('Execute request to HTTP server...');
        $raw = \curl_exec($fp);
        if (false === $raw) {
            $this->logger?->debug("Can't request to HTTP server: $server->server");
            throw new NetworkException(\curl_error($fp), \curl_errno($fp));
        }

        $this->logger?->debug($raw);

        \curl_close($fp);
        $this->logger?->debug('Close CURL connection');

        $json = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);

        return new RdapResponse($json);
    }

    /**
     * @throws TimeoutException
     * @throws NetworkException
     */
    protected function whoisRequest(Server $server, string $query): WhoisResponse
    {
        $parts = \explode(':', $server->server, 2);
        $host = $parts[0];
        $port = (int) ($parts[1] ?? 43);

        $this->logger?->debug("Open connection to WHOIS server: $server->server");
        $ptr = @\fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$ptr) {
            $this->logger?->debug("Can't connect to WHOIS server: $server->server");
            throw new NetworkException($errstr, $errno);
        }

        \stream_set_timeout($ptr, $this->timeout);
        \stream_set_blocking($ptr, false);

        $this->logger?->debug("Write data \"$query\" to WHOIS server...");
        $write = \fwrite($ptr, $query."\r\n");
        if (false === $write) {
            $this->logger?->debug("Can't write data to WHOIS $server->server");
            $error = \error_get_last();
            throw new NetworkException($error['message']);
        }

        $raw = '';
        $_ = null;
        $r = [$ptr];
        $startTime = \time();

        $this->logger?->debug('Read data from WHOIS server...');
        while (!\feof($ptr)) {
            if (false !== \stream_select($r, $_, $_, $this->timeout)) {
                $str = \fgets($ptr, $this->buffer);
                if (false !== $str) {
                    $raw .= $str;
                }

                if (\time() - $startTime > $this->timeout) {
                    $this->logger?->debug('Timeout reading from WHOIS server: '.$server->server);
                    $this->logger?->debug('Close connection');
                    @\fclose($ptr);
                    throw new TimeoutException('Timeout reading from WHOIS server: '.$server->server);
                }
            }
        }

        if ('' === $raw) {
            $this->logger?->debug('Empty data from WHOIS server: '.$server->server);
            $this->logger?->debug('Close connection');
            @\fclose($ptr);
            throw new NetworkException('Empty data from WHOIS server: '.$server->server);
        }

        $this->logger?->debug($raw);

        $this->logger?->debug('Close connection');
        \fclose($ptr);

        $raw = $this->convertToUtf8($raw);
        $raw = \mb_trim($raw);

        return new WhoisResponse($raw);
    }
}
