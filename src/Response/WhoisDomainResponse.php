<?php

declare(strict_types=1);

namespace WhoRdap\Response;

use WhoRdap\Exception\RegistrarServerException;

final readonly class WhoisDomainResponse
{
    public function __construct(
        public string $response,
        public string $server,
        public WhoisDomainRegistrarResponse|RegistrarServerException|null $registrarResponse = null,
    ) {
    }
}
