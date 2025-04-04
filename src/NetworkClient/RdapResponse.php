<?php

declare(strict_types=1);

namespace PHPWhoisLite\NetworkClient;

final readonly class RdapResponse
{
    public function __construct(
        public array $data,
    ) {
    }
}
