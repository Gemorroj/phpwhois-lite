<?php

declare(strict_types=1);

namespace PHPWhoisLite\Exception;

final class IpPrivateRangeException extends HandlerException
{
    public static function create(string $ip): self
    {
        return new self(\sprintf('The IP address "%s" is in private range.', $ip));
    }
}
