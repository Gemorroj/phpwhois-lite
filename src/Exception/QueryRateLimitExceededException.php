<?php

declare(strict_types=1);

namespace WhoRdap\Exception;

final class QueryRateLimitExceededException extends NetworkException
{
    public static function create(string $server): self
    {
        return new self(\sprintf('Query rate limit exceeded to server %s.', $server));
    }
}
