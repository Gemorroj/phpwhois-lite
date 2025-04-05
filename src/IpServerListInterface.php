<?php

declare(strict_types=1);

namespace WhoRdap;

interface IpServerListInterface
{
    public string $serverDefault { get; }
    /**
     * @var array<string, string>
     */
    public array $serversIpv4 { get; }
    /**
     * @var array<string, string>
     */
    public array $serversIpv6 { get; }
}
