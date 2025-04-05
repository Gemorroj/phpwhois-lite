<?php

declare(strict_types=1);

namespace WhoRdap;

interface AsnServerListInterface
{
    public string $serverDefault { get; }
    /**
     * @var array<string|int, string>
     */
    public array $servers { get; }
}
