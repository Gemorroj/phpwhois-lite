<?php

declare(strict_types=1);

namespace PHPWhoisLite;

enum QueryType: string
{
    case DOMAIN = 'domain';
    case IP = 'ip';
    case AS = 'as';
}
