<?php

declare(strict_types=1);

namespace WhoRdap\NetworkClient;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use WhoRdap\Exception\HttpException;
use WhoRdap\Exception\InvalidResponseException;
use WhoRdap\Exception\NetworkException;
use WhoRdap\Exception\QueryRateLimitExceededException;
use WhoRdap\Exception\TimeoutException;
use WhoRdap\NetworkClientInterface;

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
     * @throws QueryRateLimitExceededException
     * @throws InvalidArgumentException
     * @throws NetworkException
     * @throws TimeoutException
     */
    public function getWhoisResponse(string $server, string $query): string
    {
        $cacheItem = null;
        if ($this->cache) {
            $cacheKey = $this->makeCacheKey($server, $query);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $this->logger?->debug("Load cached data for $server -> $query");

                return $cacheItem->get();
            }
        }

        $response = $this->whoisRequest($server, $query);

        if ($this->isWhoisQueryRateLimitExceeded($response)) {
            throw QueryRateLimitExceededException::create($server);
        }

        if ($cacheItem) {
            $this->logger?->debug('Save cache');
            $cacheItem->set($response);
            $this->cache->save($cacheItem);
        }

        return $response;
    }

    /**
     * @throws InvalidResponseException
     * @throws InvalidArgumentException
     * @throws HttpException
     * @throws NetworkException
     */
    public function getRdapResponse(string $server, string $query): string
    {
        $cacheItem = null;
        if ($this->cache) {
            $cacheKey = $this->makeCacheKey($server, $query);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $this->logger?->debug("Load cached data for $server -> $query");

                return $cacheItem->get();
            }
        }

        $response = $this->rdapRequest($server, $query);

        if ($cacheItem) {
            $this->logger?->debug('Save cache');
            $cacheItem->set($response);
            $this->cache->save($cacheItem);
        }

        return $response;
    }

    protected function isWhoisQueryRateLimitExceeded(string $response): bool
    {
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

        return \array_any($stings, static function (string $value) use ($response): bool {
            return \str_contains($response, $value);
        });
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
     * @throws InvalidResponseException
     * @throws HttpException
     */
    protected function rdapRequest(string $server, string $query): string
    {
        $url = \rtrim($server, '/').$query;
        $this->logger?->debug("Initialize CURL connection to HTTP server: $url");
        $fp = \curl_init($url);
        if (!$fp) {
            $this->logger?->debug("Can't init CURL connection to HTTP server: $server");
            throw new NetworkException("Can't init CURL connection to HTTP server: $server");
        }
        \curl_setopt($fp, \CURLOPT_ENCODING, '');
        \curl_setopt($fp, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($fp, \CURLOPT_FOLLOWLOCATION, true); // http -> https or redirect to actual server
        \curl_setopt($fp, \CURLOPT_TIMEOUT, $this->timeout);
        \curl_setopt($fp, \CURLOPT_BUFFERSIZE, $this->buffer);
        \curl_setopt($fp, \CURLOPT_HTTPHEADER, [
            'User-Agent: WhoRdap',
        ]);

        $this->logger?->debug('Execute request to HTTP server...');
        $raw = \curl_exec($fp);
        if (false === $raw) {
            $this->logger?->debug("Can't request to HTTP server: $server");
            throw new NetworkException(\curl_error($fp), \curl_errno($fp));
        }

        $this->logger?->debug($raw);

        \curl_close($fp);
        $this->logger?->debug('Close CURL connection');

        $httpCode = \curl_getinfo($fp, \CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            throw HttpException::create($httpCode, $url, $raw);
        }

        if (!\json_validate($raw)) {
            $this->logger?->debug("Invalid JSON response: $server");
            throw new InvalidResponseException('Invalid RDAP response. Is not valid JSON.', $raw);
        }

        return $raw;
    }

    /**
     * @throws NetworkException
     * @throws TimeoutException
     */
    protected function whoisRequest(string $server, string $query): string
    {
        $parts = \explode(':', $server, 2);
        $host = $parts[0];
        $port = (int) ($parts[1] ?? 43);

        $this->logger?->debug("Open connection to WHOIS server: $server");
        $ptr = @\fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$ptr) {
            $this->logger?->debug("Can't connect to WHOIS server: $server");
            throw new NetworkException($errstr, $errno);
        }

        \stream_set_timeout($ptr, $this->timeout);
        \stream_set_blocking($ptr, false);

        $this->logger?->debug("Write data \"$query\" to WHOIS server...");
        $write = \fwrite($ptr, $query."\r\n");
        if (false === $write) {
            $this->logger?->debug("Can't write data to WHOIS $server");
            $error = \error_get_last();
            throw new NetworkException($error['message']);
        }

        $raw = '';
        $_ = null;
        $startTime = \time();

        $this->logger?->debug('Read data from WHOIS server...');
        while (!\feof($ptr)) {
            $r = [$ptr];
            if (false !== \stream_select($r, $_, $_, $this->timeout)) {
                $str = \fgets($ptr, $this->buffer);
                if (false !== $str) {
                    $raw .= $str;
                }

                if (\time() - $startTime > $this->timeout) {
                    $this->logger?->debug('Timeout reading from WHOIS server: '.$server);
                    $this->logger?->debug('Close connection');
                    @\fclose($ptr);
                    throw new TimeoutException('Timeout reading from WHOIS server: '.$server);
                }
            }
        }

        if ('' === $raw) {
            $this->logger?->debug('Empty data from WHOIS server: '.$server);
            $this->logger?->debug('Close connection');
            @\fclose($ptr);
            throw new NetworkException('Empty data from WHOIS server: '.$server);
        }

        $this->logger?->debug($raw);

        $this->logger?->debug('Close connection');
        \fclose($ptr);

        $raw = $this->convertToUtf8($raw);
        $raw = \mb_trim($raw);

        return $raw;
    }
}
