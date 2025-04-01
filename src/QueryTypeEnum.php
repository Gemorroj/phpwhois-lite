<?php

declare(strict_types=1);

namespace PHPWhoisLite;

enum QueryTypeEnum: string
{
    case IPv4 = 'ipv4';
    case IPv6 = 'ipv6';
    case AS = 'as';
    case DOMAIN = 'domain';
}
