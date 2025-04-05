<?php

declare(strict_types=1);

namespace WhoRdap;

interface NetworkClientInterface
{
    public function getWhoisResponse(string $server, string $query): string;

    public function getRdapResponse(string $server, string $query): string;
}
