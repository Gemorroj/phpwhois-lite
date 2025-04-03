<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Response\RdapResponse;
use PHPWhoisLite\Response\WhoisResponse;

interface NetworkClientInterface
{
    public function getResponse(Server $server, string $query): RdapResponse|WhoisResponse;
}
