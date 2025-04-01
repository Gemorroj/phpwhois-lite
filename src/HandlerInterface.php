<?php

declare(strict_types=1);

namespace PHPWhoisLite;

interface HandlerInterface
{
    public function process(string $query): Data;
}
