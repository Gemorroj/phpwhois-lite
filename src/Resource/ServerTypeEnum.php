<?php

declare(strict_types=1);

namespace WhoRdap\Resource;

enum ServerTypeEnum: string
{
    case WHOIS = 'whois';
    case RDAP = 'rdap';
}
