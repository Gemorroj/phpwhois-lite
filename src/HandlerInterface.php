<?php

declare(strict_types=1);

namespace PHPWhoisLite;

interface HandlerInterface
{
    public function parse(string $query): ?Data;
}
