<?php

declare(strict_types=1);

namespace PHPWhoisLite\Response;

use PHPWhoisLite\Exception\RegistrarServerException;
use PHPWhoisLite\NetworkClient\RdapResponse;
use PHPWhoisLite\NetworkClient\WhoisResponse;
use PHPWhoisLite\Resource\Server;

final readonly class DomainResponse
{
    public function __construct(
        public RdapResponse|WhoisResponse $response,
        public Server $server,
        public RdapResponse|WhoisResponse|null $registrarResponse = null,
        public ?Server $registrarServer = null,
        public ?RegistrarServerException $registrarServerException = null,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getResponseAsString(): string
    {
        if ($this->registrarResponse instanceof RdapResponse) {
            return \json_encode($this->registrarResponse->data, \JSON_THROW_ON_ERROR);
        }
        if ($this->registrarResponse instanceof WhoisResponse) {
            return $this->registrarResponse->data;
        }

        if ($this->response instanceof RdapResponse) {
            return \json_encode($this->response->data, \JSON_THROW_ON_ERROR);
        }

        return $this->response->data;
    }
}
