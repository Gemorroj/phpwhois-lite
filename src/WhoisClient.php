<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\QueryRateLimitExceededException;
use PHPWhoisLite\Exception\TimeoutException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

readonly class WhoisClient implements WhoisClientInterface
{
    public function __construct(private int $timeout = 5, private int $buffer = 1024, private ?CacheItemPoolInterface $cache = null, private ?LoggerInterface $logger = null)
    {
    }

    protected function makeCacheKey(string ...$args): string
    {
        return \md5(\serialize($args));
    }

    public function getData(string $server, string $query): string
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

        if (\str_starts_with($server, 'http://') || \str_starts_with($server, 'https://')) {
            $raw = $this->httpRequest($server, $query);
        } else {
            $raw = $this->whoisRequest($server, $query);
        }

        if (\str_contains($raw, 'Query rate limit exceeded')) {
            throw QueryRateLimitExceededException::create($server);
        }

        $raw = $this->convertToUtf8($raw);
        $raw = \mb_trim($raw);

        if ($cacheItem) {
            $this->logger?->debug('Save cache');
            $cacheItem->set($raw);
            $this->cache->save($cacheItem);
        }

        return $raw;
    }

    protected function convertToUtf8(string $raw): string
    {
        $isUtf8 = (bool) \preg_match('//u', $raw);
        if (!$isUtf8) {
            $raw = \mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1'); // fixme: detect encoding. in fact mb_detect_encoding is not working
        }

        return $raw;
    }

    protected function httpRequest(string $server, string $query): string
    {
        $this->logger?->debug("Initialize CURL connection to HTTP server: $server.$query");
        $fp = \curl_init($server.$query);
        if (!$fp) {
            $this->logger?->debug("Can't init CURL connection to HTTP server: $server");
            throw new NetworkException("Can't init CURL connection to HTTP server: $server");
        }
        \curl_setopt($fp, \CURLOPT_ENCODING, '');
        \curl_setopt($fp, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($fp, \CURLOPT_FOLLOWLOCATION, true); // http -> https
        \curl_setopt($fp, \CURLOPT_TIMEOUT, $this->timeout);
        \curl_setopt($fp, \CURLOPT_BUFFERSIZE, $this->buffer);

        $this->logger?->debug('Execute request to HTTP server...');
        $raw = \curl_exec($fp);
        if (false === $raw) {
            $this->logger?->debug("Can't request to HTTP server: $server");
            throw new NetworkException(\curl_error($fp), \curl_errno($fp));
        }

        $this->logger?->debug($raw);

        \curl_close($fp);
        $this->logger?->debug('Close CURL connection');

        $raw = \strip_tags($raw);

        return $raw;
    }

    protected function whoisRequest(string $server, string $query): string
    {
        [$host, $port] = \explode(':', $server, 2);
        $this->logger?->debug("Open connection to WHOIS server: $server");
        $ptr = @\fsockopen($host, (int) $port, $errno, $errstr, $this->timeout);
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

        return $raw;
    }
}
