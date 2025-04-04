<?php

declare(strict_types=1);

namespace WhoRdap;

use WhoRdap\NetworkClient\RdapResponse;
use WhoRdap\NetworkClient\WhoisResponse;
use WhoRdap\Resource\Server;

interface NetworkClientInterface
{
    public function getResponse(Server $server, string $query): RdapResponse|WhoisResponse;
}
