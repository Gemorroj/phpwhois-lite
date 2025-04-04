<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Response\AsnResponse;
use PHPWhoisLite\Response\DomainResponse;
use PHPWhoisLite\Response\IpResponse;

interface HandlerInterface
{
    public function process(string $query, ?Server $forceServer = null): IpResponse|AsnResponse|DomainResponse;
}
