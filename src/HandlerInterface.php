<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Resource\Server;
use PHPWhoisLite\Response\AsResponse;
use PHPWhoisLite\Response\DomainResponse;
use PHPWhoisLite\Response\IpResponse;

interface HandlerInterface
{
    public function process(string $query, ?Server $forceServer = null): IpResponse|AsResponse|DomainResponse;
}
