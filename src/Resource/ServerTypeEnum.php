<?php

declare(strict_types=1);

namespace PHPWhoisLite\Resource;

enum ServerTypeEnum: string
{
    case WHOIS = 'whois';
    case RDAP = 'rdap';
}
