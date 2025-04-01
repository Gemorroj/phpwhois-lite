<?php

declare(strict_types=1);

namespace PHPWhoisLite\Exception;

final class DomainWhoisServerNotFoundException extends \Exception
{
    public static function create(string $query): self
    {
        return new self('Cannot retrieve WHOIS server for "'.$query.'".');
    }
}
