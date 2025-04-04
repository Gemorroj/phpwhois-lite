<?php

declare(strict_types=1);

namespace PHPWhoisLite\NetworkClient;

final readonly class WhoisResponse
{
    public function __construct(
        public string $data,
    ) {
    }
}
