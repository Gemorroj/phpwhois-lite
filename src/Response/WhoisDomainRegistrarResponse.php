<?php

declare(strict_types=1);

namespace WhoRdap\Response;

final readonly class WhoisDomainRegistrarResponse
{
    public function __construct(
        public string $response,
        public string $server,
    ) {
    }
}
