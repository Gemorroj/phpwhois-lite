<?php

declare(strict_types=1);

namespace PHPWhoisLite;

use PHPWhoisLite\Resource\Server;

interface HandlerInterface
{
    public function process(string $query, ?Server $forceServer = null): Data;
}
