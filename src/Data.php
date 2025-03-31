<?php

declare(strict_types=1);

namespace PHPWhoisLite;

final readonly class Data
{
    public function __construct(public string $raw)
    {
    }
}
