<?php

declare(strict_types=1);

namespace WhoRdap;

use WhoRdap\Resource\Server;
use WhoRdap\Response\AsnResponse;
use WhoRdap\Response\DomainResponse;
use WhoRdap\Response\IpResponse;

interface HandlerInterface
{
    public function process(string $query, ?Server $forceServer = null): IpResponse|AsnResponse|DomainResponse;
}
