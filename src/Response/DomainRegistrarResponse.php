<?php

declare(strict_types=1);

namespace PHPWhoisLite\Response;

use PHPWhoisLite\NetworkClient\RdapResponse;
use PHPWhoisLite\NetworkClient\WhoisResponse;
use PHPWhoisLite\Resource\Server;

final readonly class DomainRegistrarResponse
{
    public function __construct(
        public RdapResponse|WhoisResponse $response,
        public Server $server,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getResponseAsString(): string
    {
        if ($this->response instanceof RdapResponse) {
            return \json_encode($this->response->data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        }

        return $this->response->data;
    }
}
