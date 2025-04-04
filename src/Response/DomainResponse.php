<?php

declare(strict_types=1);

namespace WhoRdap\Response;

use WhoRdap\Exception\RegistrarServerException;
use WhoRdap\NetworkClient\RdapResponse;
use WhoRdap\NetworkClient\WhoisResponse;
use WhoRdap\Resource\Server;

final readonly class DomainResponse
{
    public function __construct(
        public RdapResponse|WhoisResponse $response,
        public Server $server,
        public DomainRegistrarResponse|RegistrarServerException|null $registrarResponse = null,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getResponseAsString(): string
    {
        if ($this->response instanceof RdapResponse) {
            return \json_encode($this->response->data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        }

        return $this->response->data;
    }
}
