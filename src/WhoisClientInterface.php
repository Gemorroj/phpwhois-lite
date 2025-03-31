<?php

declare(strict_types=1);

namespace PHPWhoisLite;

interface WhoisClientInterface
{
    public function getData(string $server, string $query): ?string;
}
