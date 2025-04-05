<?php

declare(strict_types=1);

namespace WhoRdap\Response;

use WhoRdap\Exception\RegistrarServerException;

final readonly class RdapDomainResponse
{
    public function __construct(
        public string $response,
        public string $server,
        public RdapDomainRegistrarResponse|RegistrarServerException|null $registrarResponse = null,
    ) {
    }
}
