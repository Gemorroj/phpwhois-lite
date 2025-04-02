<?php

declare(strict_types=1);

namespace PHPWhoisLite;

enum QueryTypeEnum: string
{
    case IP = 'ip';
    case AS = 'as';
    case DOMAIN = 'domain';
}
