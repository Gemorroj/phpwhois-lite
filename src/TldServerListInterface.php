<?php

declare(strict_types=1);

namespace WhoRdap;

interface TldServerListInterface
{
    public string $serverDefault { get; }
    /**
     * @var array<string, string>
     */
    public array $servers { get; }
}
