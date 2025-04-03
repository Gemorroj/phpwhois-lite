<?php

declare(strict_types=1);

namespace PHPWhoisLite\Response;

final readonly class WhoisResponse
{
    public function __construct(
        public string $data,
    ) {
    }
}
