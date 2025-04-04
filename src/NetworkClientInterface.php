<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\NetworkClient\RdapResponse;
use PHPWhoisLite\NetworkClient\WhoisResponse;
use PHPWhoisLite\Resource\Server;

interface NetworkClientInterface
{
    public function getResponse(Server $server, string $query): RdapResponse|WhoisResponse;
}
