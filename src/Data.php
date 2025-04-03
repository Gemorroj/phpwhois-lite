<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Response\RdapResponse;
use PHPWhoisLite\Response\WhoisResponse;

final readonly class Data
{
    public function __construct(
        public RdapResponse|WhoisResponse $response,
        public Server $server,
        public QueryTypeEnum $type,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getResponseAsString(): string
    {
        if ($this->response instanceof RdapResponse) {
            return \json_encode($this->response->data, \JSON_THROW_ON_ERROR);
        }

        return $this->response->data;
    }
}
