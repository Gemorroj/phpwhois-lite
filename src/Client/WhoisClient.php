<?php

declare(strict_types=1);

namespace PHPWhoisLite\Client;

use PHPWhoisLite\Exception\NetworkException;
use PHPWhoisLite\Exception\TimeoutException;
use PHPWhoisLite\WhoisClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

readonly class WhoisClient implements WhoisClientInterface
{
    public function __construct(private int $timeout = 2, private int $buffer = 1024, private ?CacheItemPoolInterface $cache = null, private ?LoggerInterface $logger = null)
    {
    }

    protected function makeCacheKey(string ...$args): string
    {
        return \md5(\serialize($args));
    }

    public function getData(string $server, string $query): ?string
    {
        [$host, $port] = \explode(':', $server, 2);
        $cacheItem = null;
        if ($this->cache) {
            $cacheKey = $this->makeCacheKey($server, $query);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $this->logger?->debug("Load cached data for $server -> $query");

                return $cacheItem->get();
            }
        }

        $this->logger?->debug("Open connection to $server");
        $ptr = @\fsockopen($host, (int) $port, $errno, $errstr, $this->timeout);
        if (!$ptr) {
            $this->logger?->debug("Can't connect to $server");
            throw new NetworkException($errstr, $errno);
        }

        \stream_set_timeout($ptr, $this->timeout);
        \stream_set_blocking($ptr, false);

        $this->logger?->debug("Write data \"$query\"");
        $write = \fwrite($ptr, $query."\r\n");
        if (false === $write) {
            $this->logger?->debug("Can't write data to $server");
            $error = \error_get_last();
            throw new NetworkException($error['message']);
        }

        $raw = '';
        $_ = null;
        $r = [$ptr];
        $startTime = \time();

        $this->logger?->debug('Read data...');
        while (!\feof($ptr)) {
            if (false !== \stream_select($r, $_, $_, $this->timeout)) {
                $str = \fgets($ptr, $this->buffer);
                if (false !== $str) {
                    $raw .= $str;
                }

                if (\time() - $startTime > $this->timeout) {
                    $this->logger?->debug('Timeout reading from '.$server);
                    $this->logger?->debug('Close connection');
                    @\fclose($ptr);
                    throw new TimeoutException('Timeout reading from '.$server);
                }
            }
        }
        $this->logger?->debug($raw);

        @\fclose($ptr);
        $this->logger?->debug('Close connection');

        $raw = '' === $raw ? null : $raw;

        if ($cacheItem && null !== $raw) {
            $this->logger?->debug('Save cache');
            $cacheItem->set($raw);
            $this->cache->save($cacheItem);
        }

        return $raw;
    }
}
